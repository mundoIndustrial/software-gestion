<?php

namespace App\Models;

use App\Traits\HasLegibleEstado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Cliente;
use App\Services\CalculadorDiasService;
use App\Events\PedidoCreado;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PedidoProduccion extends Model
{
    use SoftDeletes, HasLegibleEstado;

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
        'aprobado_por_supervisor_en',
        'motivo_anulacion',
        'fecha_anulacion',
        'usuario_anulacion',
    ];

    protected $casts = [
        'fecha_de_creacion_de_orden' => 'datetime',
        'fecha_estimada_de_entrega' => 'datetime',
        'estado' => 'string',
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

        // Disparar evento cuando se crea un pedido
        static::created(function ($model) {
            $asesor = $model->asesora;
            if ($asesor) {
                event(new PedidoCreado($model, $asesor));
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
     * Relación: Un pedido tiene historial de cambios de estado
     */
    public function historialCambios(): HasMany
    {
        return $this->hasMany(HistorialCambiosPedido::class, 'pedido_id');
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
     * 
     * Si la orden TIENE cotización: muestra descripción completa con detalles usando template
     * Si la orden NO tiene cotización: muestra solo nombres de prendas enumerados
     * 
     * Formato SIN cotización:
     * PRENDA 1: CAMISA DRILL
     * DESCRIPCION: Logo bordado en espalda
     * TALLAS: S:50, M:50, L:50
     * 
     * Formato CON cotización (con template estructurado):
     * 1: CAMISA DRILL
     * Color: NARANJA | Tela: DRILL BORNEO REF:REF-DB-001 | Manga: LARGA
     * 
     * DESCRIPCIÓN:
     * - Logo: Logo bordado en espalda
     * 
     * Bolsillos:
     * • Pecho
     * • Espalda
     * 
     * Reflectivo:
     * • Mangas
     * 
     * TALLAS:
     * - S: 50
     * - M: 50
     */
    public function getDescripcionPrendasAttribute()
    {
        if (!$this->relationLoaded('prendas') || $this->prendas->isEmpty()) {
            return '';
        }

        // Generar descripción detallada para TODAS las prendas
        // (tenga cotización o no)
        $descripciones = $this->prendas->map(function($prenda, $index) {
            return $prenda->generarDescripcionDetallada($index + 1);
        })->toArray();

        return implode("\n\n", $descripciones);
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
            ->orderBy('updated_at', 'desc')
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
        $ultimaFecha = ProcesoPrenda::where('numero_pedido', $this->numero_pedido)
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

    /**
     * Calcular días hábiles desde la creación de la orden
     * Si el estado es "Entregado", calcula hasta la fecha del proceso "Despacho"
     */
    public function calcularDiasHabiles()
    {
        if (!$this->fecha_de_creacion_de_orden) {
            return '-';
        }

        $diasCalculados = 0;
        $fechaInicio = $this->fecha_de_creacion_de_orden;
        $fechaFin = now();
        
        // Si el estado es "Entregado", buscar la fecha del proceso "Despacho"
        if ($this->estado === 'Entregado') {
            $procesoDespacho = DB::table('procesos_prenda')
                ->where('numero_pedido', $this->numero_pedido)
                ->where('proceso', 'Despacho')
                ->select('fecha_inicio')
                ->first();
            
            if ($procesoDespacho && $procesoDespacho->fecha_inicio) {
                $fechaFin = Carbon::parse($procesoDespacho->fecha_inicio);
            }
        }
        
        // Festivos colombianos fijos
        $anio = $fechaInicio->year;
        $festivos = [
            $fechaInicio->copy()->setMonth(1)->setDay(1)->toDateString(),   // Año Nuevo
            $fechaInicio->copy()->setMonth(5)->setDay(1)->toDateString(),   // Día del Trabajo
            $fechaInicio->copy()->setMonth(7)->setDay(1)->toDateString(),   // Día de la Independencia
            $fechaInicio->copy()->setMonth(7)->setDay(20)->toDateString(),  // Grito de Independencia
            $fechaInicio->copy()->setMonth(8)->setDay(7)->toDateString(),   // Batalla de Boyacá
            $fechaInicio->copy()->setMonth(12)->setDay(8)->toDateString(),  // Inmaculada Concepción
            $fechaInicio->copy()->setMonth(12)->setDay(25)->toDateString(), // Navidad
        ];
        
        // Agregar festivos del siguiente año si es necesario
        if ($fechaFin->year > $fechaInicio->year) {
            $anioFin = $fechaFin->year;
            $festivos = array_merge($festivos, [
                $fechaInicio->copy()->setYear($anioFin)->setMonth(1)->setDay(1)->toDateString(),
                $fechaInicio->copy()->setYear($anioFin)->setMonth(5)->setDay(1)->toDateString(),
                $fechaInicio->copy()->setYear($anioFin)->setMonth(7)->setDay(1)->toDateString(),
                $fechaInicio->copy()->setYear($anioFin)->setMonth(7)->setDay(20)->toDateString(),
                $fechaInicio->copy()->setYear($anioFin)->setMonth(8)->setDay(7)->toDateString(),
                $fechaInicio->copy()->setYear($anioFin)->setMonth(12)->setDay(8)->toDateString(),
                $fechaInicio->copy()->setYear($anioFin)->setMonth(12)->setDay(25)->toDateString(),
            ]);
        }
        
        // Calcular días hábiles
        $actual = $fechaInicio->copy();
        while ($actual <= $fechaFin) {
            // Verificar si no es sábado (6) ni domingo (0)
            if ($actual->dayOfWeek !== 0 && $actual->dayOfWeek !== 6) {
                // Verificar si no es festivo
                if (!in_array($actual->toDateString(), $festivos)) {
                    $diasCalculados++;
                }
            }
            $actual->addDay();
        }
        
        // Restar 1 porque no se cuenta el día de inicio
        $diasCalculados = max(0, $diasCalculados - 1);
        
        return $diasCalculados > 0 ? $diasCalculados . ' día' . ($diasCalculados > 1 ? 's' : '') : '-';
    }

    /**
     * Obtener nombres de prendas separados por coma
     */
    public function getNombresPrendas()
    {
        if (!$this->prendas || $this->prendas->count() === 0) {
            return '-';
        }

        return $this->prendas
            ->pluck('nombre_prenda')
            ->unique()
            ->implode(', ') ?: '-';
    }

    /**
     * Relación: Un pedido tiene muchas prendas normalizadas (nuevas tablas DDD)
     */
    public function prendasPed(): HasMany
    {
        return $this->hasMany(PrendaPedido::class, 'numero_pedido', 'numero_pedido');
    }

    /**
     * Relación: Un pedido tiene un logo (nuevas tablas DDD)
     */
    public function logo(): HasMany
    {
        return $this->hasMany(LogoPed::class, 'pedido_produccion_id');
    }

    /**
     * Constantes de estados y opciones
     */
    const ESTADOS = ['Pendiente', 'No iniciado', 'En Ejecución', 'Entregado', 'Anulada'];
    const DIAS_ENTREGA = [15, 20, 25, 30];
}
