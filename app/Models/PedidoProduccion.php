<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Cliente;
use App\Services\CalculadorDiasService;

class PedidoProduccion extends Model
{
    use SoftDeletes;

    protected $table = 'pedidos_produccion';

    protected $fillable = [
        'cotizacion_id',
        'numero_cotizacion',
        'numero_pedido',
        'cliente',
        'cliente_id',
        'novedades',
        'asesor_id',
        'forma_de_pago',
        'estado',
        'area',
        'fecha_ultimo_proceso',
        'fecha_de_creacion_de_orden',
        'dia_de_entrega',
        'fecha_estimada_de_entrega',
    ];

    protected $casts = [
        'fecha_de_creacion_de_orden' => 'date',
        'fecha_estimada_de_entrega' => 'date',
    ];

    protected $appends = [
        'descripcion_prendas',
        'cantidad_total',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-calcular fecha_estimada_de_entrega cuando se guarda la orden
        static::saving(function ($model) {
            // Si se está actualizando dia_de_entrega y fecha_estimada_de_entrega está vacía o debe recalcularse
            if ($model->isDirty('dia_de_entrega') && $model->fecha_de_creacion_de_orden) {
                $fechaEstimada = $model->calcularFechaEstimada();
                if ($fechaEstimada) {
                    $model->fecha_estimada_de_entrega = $fechaEstimada;
                }
            }
        });

        // Nota: Los procesos se crean automáticamente cuando se crean prendas
        // No crear procesos aquí, ya que no tenemos prendas aún
    }

    /**
     * Relación: Un pedido pertenece a un usuario (asesora)
     */
    public function asesora(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /**
     * Relación: Un pedido pertenece a un cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relación: Un pedido pertenece a una cotización
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    /**
     * Relación: Un pedido tiene muchas prendas (via numero_pedido)
     */
    public function prendas(): HasMany
    {
        return $this->hasMany(PrendaPedido::class, 'numero_pedido', 'numero_pedido');
    }

    /**
     * Relación: Un pedido tiene muchos materiales de insumos
     */
    public function materiales(): HasMany
    {
        return $this->hasMany(MaterialesOrdenInsumos::class, 'pedido_produccion_id');
    }

    /**
     * Obtener descripción de prendas (concatenadas con detalles)
     * Extrae información de la cotización si está disponible
     * Formato:
     * Prenda 1: NOMBRE
     * Descripción: descripcion completa
     * Tallas: S:1, M:4, L:3, ...
     */
    public function getDescripcionPrendasAttribute()
    {
        if (!$this->relationLoaded('prendas') || $this->prendas->isEmpty()) {
            \Log::warning('DEBUG: No hay prendas cargadas o relación no está cargada');
            return 'Sin prendas';
        }

        // 🔧 Usar descripcion_armada que contiene la descripción formateada con saltos de línea
        \Log::info('DEBUG: Prendas cargadas', [
            'cantidad' => $this->prendas->count(),
            'prendas' => $this->prendas->map(function($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre_prenda,
                    'descripcion_armada' => $p->descripcion_armada ? substr($p->descripcion_armada, 0, 50) : 'NULL'
                ];
            })->toArray()
        ]);
        
        $prendas = $this->prendas->map(function($prenda, $index) {
            return $prenda->descripcion_armada ?? '';
        })->filter()->join("\n\n");

        \Log::info('DEBUG: Descripción final', [
            'resultado' => $prendas ? substr($prendas, 0, 100) : 'VACÍO'
        ]);

        return $prendas ?: 'Sin prendas';
    }

    /**
     * Obtener cantidad total de prendas
     */
    public function getCantidadTotalAttribute()
    {
        if (!$this->relationLoaded('prendas') || $this->prendas->isEmpty()) {
            return 0;
        }

        return $this->prendas->sum('cantidad');
    }

    /**
     * Calcular fecha estimada de entrega basada en día_de_entrega
     * Suma días hábiles a partir de fecha_de_creacion_de_orden
     */
    public function calcularFechaEstimada()
    {
        if (!$this->fecha_de_creacion_de_orden || !$this->dia_de_entrega) {
            return null;
        }

        try {
            $fechaInicio = \Carbon\Carbon::parse($this->fecha_de_creacion_de_orden);
            $diasAñadir = intval($this->dia_de_entrega);
            
            // Obtener festivos
            $festivos = \App\Models\Festivo::pluck('fecha')->toArray();
            
            // Contar días hábiles
            $fecha = $fechaInicio->copy();
            $diasContados = 0;
            
            while ($diasContados < $diasAñadir) {
                $fecha->addDay();
                
                // Saltar fines de semana
                if ($fecha->isWeekend()) {
                    continue;
                }
                
                // Saltar festivos
                if (in_array($fecha->format('Y-m-d'), $festivos)) {
                    continue;
                }
                
                $diasContados++;
            }
            
            return $fecha;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtener el área actual del pedido basado en el último proceso registrado
     * ADVERTENCIA: Este método causa N+1 queries. Usar procesoActualOptimizado() si los procesos ya están cargados.
     */
    public function getAreaActual()
    {
        // Obtener el último proceso de cualquiera de las prendas del pedido
        $ultimoProceso = ProcesoPrenda::whereIn('prenda_pedido_id', $this->prendas()->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($ultimoProceso) {
            return $ultimoProceso->proceso;
        }

        // Si no hay procesos, retornar "Creación Orden"
        return 'Creación Orden';
    }

    /**
     * Obtener el área actual de forma optimizada (cuando procesos ya están eager-loaded)
     * Usar este método en listados para evitar N+1 queries
     */
    public function procesoActualOptimizado()
    {
        // Este método asume que los procesos ya fueron cargados via eager loading
        // Procesa los datos en memoria sin queries adicionales
        
        $ultimoProceso = $this->prendas
            ->flatMap(fn($prenda) => $prenda->procesos)
            ->sortByDesc('created_at')
            ->first();

        return $ultimoProceso?->proceso ?? 'Creación Orden';
    }

    /**
     * Relación: Un pedido tiene muchos procesos (directa via numero_pedido)
     */
    public function procesos(): HasMany
    {
        return $this->hasMany(ProcesoPrenda::class, 'numero_pedido', 'numero_pedido');
    }

    /**
     * Calcular el total de días del pedido (desde creación hasta fin)
     */
    public function getTotalDias()
    {
        if (!$this->fecha_de_creacion_de_orden) {
            return null;
        }

        // Obtener la última fecha de fin de todos los procesos
        $ultimaFecha = ProcesoPrenda::whereIn('prenda_pedido_id', $this->prendas()->pluck('id'))
            ->whereNotNull('fecha_fin')
            ->max('fecha_fin');

        if (!$ultimaFecha) {
            // Si no hay procesos completados, usar hoy como referencia
            $ultimaFecha = now()->toDateString();
        }

        $dias = CalculadorDiasService::calcularDiasHabiles(
            $this->fecha_de_creacion_de_orden,
            $ultimaFecha
        );

        return CalculadorDiasService::formatearDias($dias);
    }

    /**
     * Obtener los días totales como número
     */
    public function getTotalDiasNumero()
    {
        $totalDias = $this->getTotalDias();
        
        if (!$totalDias) {
            return 0;
        }

        preg_match('/(\d+)/', $totalDias, $matches);
        return isset($matches[1]) ? (int) $matches[1] : 0;
    }

    /**
     * Obtener el desglose de días por proceso
     */
    public function getDesgloseDiasPorProceso()
    {
        $procesos = $this->procesos()
            ->whereNotNull('fecha_fin')
            ->with('prenda')
            ->get()
            ->groupBy('proceso');

        $desglose = [];

        foreach ($procesos as $nombreProceso => $procesoGroup) {
            $totalDiasProc = 0;

            foreach ($procesoGroup as $proceso) {
                if ($proceso->fecha_inicio && $proceso->fecha_fin) {
                    $dias = CalculadorDiasService::calcularDiasHabiles(
                        $proceso->fecha_inicio,
                        $proceso->fecha_fin
                    );
                    $totalDiasProc += $dias;
                }
            }

            if ($totalDiasProc > 0) {
                $desglose[$nombreProceso] = CalculadorDiasService::formatearDias($totalDiasProc);
            }
        }

        return $desglose;
    }

    /**
     * Verificar si el pedido está en retraso
     */
    public function estaEnRetraso()
    {
        if (!$this->fecha_estimada_de_entrega) {
            return false;
        }

        $areaActual = $this->getAreaActual();

        // Si ya está entregado o despachado, no está en retraso
        if (in_array($areaActual, ['Entrega', 'Despacho'])) {
            return false;
        }

        $fechaEstimada = $this->fecha_estimada_de_entrega instanceof \Carbon\Carbon 
            ? $this->fecha_estimada_de_entrega->toDateString()
            : $this->fecha_estimada_de_entrega;

        return now()->toDateString() > $fechaEstimada;
    }

    /**
     * Obtener los días de retraso
     */
    public function getDiasDeRetraso()
    {
        if (!$this->estaEnRetraso()) {
            return 0;
        }

        $dias = CalculadorDiasService::calcularDiasHabiles(
            $this->fecha_estimada_de_entrega,
            now()
        );

        return $dias;
    }
}
