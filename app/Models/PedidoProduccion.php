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
        'cantidad_total',
        'aprobado_por_usuario_cartera',
        'aprobado_por_cartera_en',
        'rechazado_por_usuario_cartera',
        'rechazado_por_cartera_en',
        'motivo_rechazo_cartera',
    ];

    protected $casts = [
        'fecha_de_creacion_de_orden' => 'datetime',
        'fecha_estimada_de_entrega' => 'datetime',
        'estado' => 'string',
    ];

    protected $appends = [
        'descripcion_prendas',
        'numero_pedido_mostrable',
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
            \Log::info(' [PedidoProduccion.boot] Hook created disparado', [
                'pedido_id' => $model->id,
                'numero_pedido' => $model->numero_pedido,
                'tiene_cotizacion' => !is_null($model->cotizacion_id),
            ]);
            
            // Solo disparar evento si hay cotización asociada
            if (!$model->cotizacion_id) {
                \Log::info('[PedidoProduccion.boot] Sin cotización, saltando evento PedidoCreado', [
                    'pedido_id' => $model->id,
                ]);
                return;
            }
            
            $asesor = $model->asesora;
            
            if ($asesor) {
                \Log::info('📤 [PedidoProduccion.boot] Disparando evento PedidoCreado', [
                    'pedido_id' => $model->id,
                    'asesor_id' => $asesor->id,
                ]);
                event(new PedidoCreado($model, $asesor));
            } else {
                \Log::warning(' [PedidoProduccion.boot] Asesor no encontrado para pedido', [
                    'pedido_id' => $model->id,
                    'asesor_id' => $model->asesor_id,
                ]);
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
     * Alias para asesora (compatibilidad)
     */
    public function asesor(): BelongsTo
    {
        return $this->asesora();
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
     * Relación: Un pedido tiene muchas prendas
     * 
     * ACTUALIZACIÓN [16/01/2026]:
     * - Foreign Key: pedido_produccion_id (antes numero_pedido)
     * - Las prendas se crean con $pedido->prendas()->create($data)
     * - Esto asegura que pedido_produccion_id se asigna automáticamente
     */
    public function prendas(): HasMany
    {
        return $this->hasMany(PrendaPedido::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Un pedido tiene muchos materiales de insumos
     */
    public function materiales(): HasMany
    {
        return $this->hasMany(MaterialesOrdenInsumos::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Un pedido tiene muchos EPP agregados
     */
    public function epps()
    {
        return $this->hasMany(PedidoEpp::class, 'pedido_produccion_id')
            ->with('epp');  // Cargar también los datos del EPP
    }

    /**
     * Relación: Acceso directo a registros de pedido_epp
     */
    public function pedidoEpps(): HasMany
    {
        return $this->hasMany(PedidoEpp::class, 'pedido_id');
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

        $resultado = implode("\n\n", $descripciones);
        
        \Log::info(' [getDescripcionPrendasAttribute] Resultado final:', [
            'numero_pedido' => $this->numero_pedido,
            'total_prendas' => count($descripciones),
            'primeros_100_caracteres' => substr($resultado, 0, 100),
        ]);
        
        return $resultado;
    }

    /**
     * Obtener cantidad total de prendas
     */
    public function getCantidadTotalAttribute()
    {
        if (!$this->relationLoaded('prendas') || $this->prendas->isEmpty()) {
            return 0;
        }

        $total = 0;
        foreach ($this->prendas as $prenda) {
            $total += $prenda->cantidad_total;
        }
        return $total;
    }

    /**
     * Obtener el número de pedido que se debe mostrar
     * Si es LOGO, muestra el número de LOGO (LOGO-00001)
     * Si no, muestra el número de pedido normal
     */
    public function getNumeroPedidoMostrableAttribute()
    {
        return $this->getNumeroPedidoMostrable();
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
        $ultimoProceso = ProcesoPrenda::where('numero_pedido', $this->numero_pedido)
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
        
        $procesos = $this->prendas
            ->flatMap(fn($prenda) => $prenda->procesos)
            ->unique('proceso');
        
        if ($procesos->isEmpty()) {
            return 'Pendiente';
        }
        
        // Orden de prioridad de estados y procesos
        $estadosPrioritarios = ['En Progreso', 'En Progreso', 'Pendiente'];
        $procesosPrioritarios = [
            'Despacho',
            'Insumos y Telas',
            'Costura',
            'Corte',
            'Control Calidad',
            'Creación de Orden',
            'tcc'
        ];
        
        // Prioridad 1: Buscar proceso "En Progreso" (que sea uno de los principales)
        foreach ($procesosPrioritarios as $nombreProceso) {
            $proceso = $procesos
                ->where('estado_proceso', 'En Progreso')
                ->where('proceso', $nombreProceso)
                ->first();
            
            if ($proceso) {
                return $proceso->proceso;
            }
        }
        
        // Prioridad 2: Buscar proceso "Pendiente" (que sea uno de los principales)
        foreach ($procesosPrioritarios as $nombreProceso) {
            $proceso = $procesos
                ->where('estado_proceso', 'Pendiente')
                ->where('proceso', $nombreProceso)
                ->first();
            
            if ($proceso) {
                return $proceso->proceso;
            }
        }
        
        // Prioridad 3: Buscar cualquier proceso que NO esté completado
        foreach ($procesosPrioritarios as $nombreProceso) {
            $proceso = $procesos
                ->where('proceso', $nombreProceso)
                ->whereNotIn('estado_proceso', ['Completado', 'Pausado'])
                ->first();
            
            if ($proceso) {
                return $proceso->proceso;
            }
        }
        
        // Prioridad 4: El último proceso creado
        $ultimoProceso = $procesos->sortByDesc('created_at')->first();
        
        return $ultimoProceso?->proceso ?? 'Pendiente';
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
     * 
     * REFACTORIZADO: Ahora usa pedido_produccion_id en lugar de numero_pedido
     */
    public function prendasPed(): HasMany
    {
        return $this->hasMany(PrendaPedido::class, 'pedido_produccion_id', 'id');
    }

    /**
     * Relación: Un pedido tiene un LOGO (nuevas tablas LOGO DDD)
     */
    public function logoPedidos(): HasMany
    {
        return $this->hasMany(LogoPedido::class, 'pedido_id', 'id');
    }

    /**
     * Obtener el LOGO pedido si existe
     */
    public function logoPedido()
    {
        return $this->logoPedidos()->first();
    }

    /**
     * Determinar si este pedido es de tipo LOGO
     */
    public function esLogo(): bool
    {
        return $this->logoPedido() !== null;
    }

    /**
     * Obtener el número de pedido correcto según el tipo
     * Si es LOGO, retorna el número de logo_pedidos (LOGO-00001)
     * Si no, retorna el número de pedidos_produccion
     */
    public function getNumeroPedidoMostrable(): string
    {
        if ($this->esLogo()) {
            return $this->logoPedido()?->numero_pedido ?? $this->numero_pedido ?? '';
        }
        return $this->numero_pedido ?? '';
    }

    /**
     * Relación anterior (mantener por compatibilidad)
     */
    public function logo(): HasMany
    {
        return $this->hasMany(LogoPed::class, 'pedido_produccion_id');
    }

    /**
     * Constantes de estados y opciones
     */
    const ESTADOS = ['Pendiente', 'No iniciado', 'En Ejecución', 'Entregado', 'Anulada'];
    const DIAS_ENTREGA = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35];
}
