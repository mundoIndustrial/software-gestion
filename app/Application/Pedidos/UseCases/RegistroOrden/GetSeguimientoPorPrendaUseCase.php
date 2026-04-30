<?php

namespace App\Application\Pedidos\UseCases\RegistroOrden;

use Carbon\Carbon;
use App\Application\Pedidos\Services\PrendaPedidoQuantityCalculator;
use App\Infrastructure\Repositories\PedidoProduccionTrackingRepository;
use App\Infrastructure\Repositories\ConsecutivosRecibosRepository;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Services\CalculadorDiasService;
use Illuminate\Support\Facades\Log;

/**
 * GetSeguimientoPorPrendaUseCase
 * Orquesta la obtención del seguimiento de prendas por pedido
 * Cumple DDD: Application Layer - UseCase
 * Delega queries a Repositories, lógica de negocio aquí
 */
class GetSeguimientoPorPrendaUseCase
{
    private PedidoProduccionTrackingRepository $pedidoRepository;
    private ConsecutivosRecibosRepository $consecutivosRepository;
    private PrendaPedidoQuantityCalculator $prendaQuantityCalculator;

    public function __construct(
        PedidoProduccionTrackingRepository $pedidoRepository,
        ConsecutivosRecibosRepository $consecutivosRepository,
        PrendaPedidoQuantityCalculator $prendaQuantityCalculator
    ) {
        $this->pedidoRepository = $pedidoRepository;
        $this->consecutivosRepository = $consecutivosRepository;
        $this->prendaQuantityCalculator = $prendaQuantityCalculator;
    }

    /**
     * Ejecutar el use case
     * GET /registros/{pedido}/seguimiento-prenda
     */
    public function execute(string $pedido, ?string $prendaId = null, ?string $numeroRecibo = null, ?string $pedidoParcialId = null, ?string $tipoRecibo = null): array
    {
        try {
            \Log::info('[GetSeguimientoPorPrendaUseCase] Iniciando consulta', [
                'pedido_numero' => $pedido
            ]);

            $pedidoModel = $this->pedidoRepository->obtenerPorIdONumero($pedido);
            
            if (!$pedidoModel) {
                return [
                    'success' => false,
                    'error' => 'Pedido no encontrado',
                    'pedido' => $pedido
                ];
            }

            $pedidoId = $pedidoModel->id;

            \Log::info('[GetSeguimientoPorPrendaUseCase] Pedido encontrado', [
                'numero_pedido' => $pedido,
                'pedido_id' => $pedidoId
            ]);

            $prendas = $this->obtenerPrendasConSeguimiento($pedidoId, $pedidoModel, $prendaId, $numeroRecibo, $pedidoParcialId, $tipoRecibo);

            return [
                'success' => true,
                'pedido' => [
                    'id' => $pedidoModel->id,
                    'numero_pedido' => $pedidoModel->numero_pedido,
                    'cliente' => $pedidoModel->cliente,
                    'created_at' => $pedidoModel->created_at,
                    'recibo_principal' => $this->resolveReciboPrincipal($prendas),
                ],
                'prendas' => $prendas,
                'areas_config' => [
                    'areas_que_requieren_encargado' => ['corte', 'costura', 'control de calidad'],
                    'areas_con_selector_dinamico' => ['corte', 'costura'],
                ],
            ];

        } catch (\Exception $e) {
            \Log::error('[GetSeguimientoPorPrendaUseCase] Error: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Error al obtener seguimiento por prenda',
                'pedido' => $pedido,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener prendas con seguimiento completo
     */
    private function obtenerPrendasConSeguimiento(int $pedidoId, $pedidoModel, ?string $prendaId = null, ?string $numeroRecibo = null, ?string $pedidoParcialId = null, ?string $tipoRecibo = null): array
    {
        $prendasQuery = PrendaPedido::where('pedido_produccion_id', $pedidoId)
            ->with(['variantes', 'procesos.tipoProceso', 'tallas']);

        if ($prendaId !== null && trim((string) $prendaId) !== '') {
            $prendasQuery->where('id', (int) $prendaId);
        }

        $prendasDB = $prendasQuery->get();

        $prendasConSeguimiento = [];

        foreach ($prendasDB as $prenda) {
            $seguimiento = $this->construirSeguimientoPrenda($prenda, $pedidoId, $pedidoModel, $numeroRecibo, $pedidoParcialId, $tipoRecibo);
            $prendasConSeguimiento[] = $seguimiento;
        }

        return $prendasConSeguimiento;
    }

    /**
     * Construir objeto de seguimiento para una prenda
     */
    private function construirSeguimientoPrenda($prenda, int $pedidoId, $pedidoModel, ?string $numeroReciboObjetivo = null, ?string $pedidoParcialId = null, ?string $tipoReciboObjetivo = null): array
    {
        $consecutivos = $this->consecutivosRepository->obtenerTodosPorPrenda($prenda->id, $pedidoId);

        // DEBUG: Log de consecutivos retornados
        $consecutivosDebug = [];
        foreach ($consecutivos as $c) {
            $arrayData = is_array($c) ? $c : (array) $c;
            $consecutivosDebug[] = [
                'tipo_recibo' => $arrayData['tipo_recibo'] ?? null,
                'activo' => $arrayData['activo'] ?? null,
                'consecutivo_actual' => $arrayData['consecutivo_actual'] ?? null,
            ];
        }
        \Log::info('[construirSeguimientoPrenda] Consecutivos obtenidos', [
            'prenda_id' => $prenda->id,
            'prenda_nombre' => $prenda->nombre_prenda,
            'total_consecutivos' => count($consecutivosDebug),
            'consecutivos' => $consecutivosDebug,
        ]);

        $numeroReciboCostura = null;
        $reciboCosturaId = null;
        $numeroReciboObjetivoNormalizado = trim((string) ($numeroReciboObjetivo ?? ''));
        $tieneNumeroObjetivo = $numeroReciboObjetivoNormalizado !== '' && is_numeric($numeroReciboObjetivoNormalizado);
        
        // Determinar el tipo de recibo objetivo (por defecto COSTURA, pero puede ser REFLECTIVO, BORDADO, etc.)
        $tipoReciboObjetivoNormalizado = strtoupper(trim((string) ($tipoReciboObjetivo ?? 'COSTURA')));

        if ($tieneNumeroObjetivo) {
            $numeroObjetivo = (int) $numeroReciboObjetivoNormalizado;
            foreach ($consecutivos as $c) {
                if (($c->tipo_recibo ?? null) === $tipoReciboObjetivoNormalizado && (int) ($c->consecutivo_actual ?? 0) === $numeroObjetivo) {
                    $numeroReciboCostura = $numeroObjetivo;
                    $reciboCosturaId = $c->id ?? null;
                    break;
                }
            }
        }

        if ($numeroReciboCostura === null) {
            foreach ($consecutivos as $c) {
                if (($c->tipo_recibo ?? null) === $tipoReciboObjetivoNormalizado && !empty($c->consecutivo_actual)) {
                    $numeroReciboCostura = (int) $c->consecutivo_actual;
                    $reciboCosturaId = $c->id ?? null;
                    break;
                }
            }
        }

        $numeroPedido = $pedidoModel ? (string) $pedidoModel->numero_pedido : null;

        if (!$numeroPedido) {
            return [
                'id' => $prenda->id,
                'nombre' => $prenda->nombre_prenda ?? 'Sin nombre',
                'seguimientos' => [],
                'procesos' => [],
                'recibos_especiales' => [],
            ];
        }

        $procesosSeguimientoData = $this->obtenerYCalcularProcesos(
            $numeroPedido,
            $prenda->id,
            $numeroReciboCostura,
            $reciboCosturaId,
            $tieneNumeroObjetivo
        );
        
        $procesosSeguimiento = $procesosSeguimientoData['procesos'] ?? [];
        $fechaPrimerProceso = $procesosSeguimientoData['fecha_primer_proceso'] ?? null;

        $seguimientosPorArea = $this->agruparProcesosPorArea($procesosSeguimiento);

        $seguimientosPorArea = $this->inyectarAreaInsumos($seguimientosPorArea, $consecutivos, $fechaPrimerProceso);

        $datosActivacion = $this->calcularDatosActivacionRecibo($consecutivos, $pedidoModel, $pedidoParcialId, $numeroReciboObjetivo, $tipoReciboObjetivo);

        $cantidadTalla = [];
        foreach ($prenda->tallas as $talla) {
            $cantidadTalla[$talla->talla] = $talla->cantidad;
        }

        $procesosArray = [];
        foreach ($prenda->procesos as $proceso) {
            $procesosArray[] = [
                'id' => $proceso->id,
                'tipo_proceso_id' => $proceso->tipo_proceso_id,
                'tipo_proceso' => $proceso->tipoProceso ? [
                    'id' => $proceso->tipoProceso->id,
                    'nombre' => $proceso->tipoProceso->nombre,
                    'slug' => $proceso->tipoProceso->slug,
                    'color' => $proceso->tipoProceso->color,
                    'icono' => $proceso->tipoProceso->icono,
                ] : null,
                'estado' => $proceso->estado,
                'observaciones' => $proceso->observaciones,
                'ubicaciones' => $proceso->ubicaciones,
            ];
        }

        $recibosEspeciales = $this->obtenerRecibosEspeciales($consecutivos, $prenda->id, $prenda->procesos);
        
        // DEBUG: Log de recibos especiales obtenidos
        \Log::info('[construirSeguimientoPrenda] Recibos especiales obtenidos', [
            'prenda_id' => $prenda->id,
            'prenda_nombre' => $prenda->nombre_prenda,
            'total_recibos_especiales' => count($recibosEspeciales),
            'recibos_especiales' => $recibosEspeciales,
        ]);

        $datosLista = [
            'id' => $prenda->id,
            'nombre_prenda' => $prenda->nombre_prenda,
            'descripcion' => $prenda->descripcion,
            'cantidad' => $this->prendaQuantityCalculator->calculate($prenda),
            'cantidad_talla' => $cantidadTalla,
            'de_bodega' => $prenda->de_bodega,
            'seguimientos_por_area' => $seguimientosPorArea,
            'procesos' => $procesosArray,
            'consecutivos' => $consecutivos->toArray(),
            'datos_activacion_recibo' => $datosActivacion,
            'area_mas_reciente' => $this->obtenerAreaMasReciente($consecutivos),
            'recibos_especiales' => $recibosEspeciales,
        ];

        \Log::info('[construirSeguimientoPrenda] DATOS FINALES CON ACTIVACION', [
            'prenda_id' => $prenda->id,
            'datos_activacion_recibo' => $datosActivacion,
        ]);

        return $datosLista;
    }

    /**
     * Obtener procesos y calcular duraciones
     */
    private function obtenerYCalcularProcesos(
        string $numeroPedido,
        int $prendaId,
        ?int $numeroReciboCostura,
        ?int $reciboCosturaId,
        bool $forzarNumeroRecibo = false
    ): array {
        \Log::info('[GetSeguimientoPorPrendaUseCase::obtenerYCalcularProcesos] Buscando procesos', [
            'numero_pedido' => $numeroPedido,
            'prenda_pedido_id' => $prendaId,
            'numero_recibo_costura' => $numeroReciboCostura,
            'recibo_costura_id' => $reciboCosturaId
        ]);

        $baseQuery = ProcesoPrenda::where('numero_pedido', $numeroPedido)
            ->whereNull('deleted_at');

        if ($numeroReciboCostura) {
            // Filtrar por recibo objetivo para evitar mezclar encargados entre recibos de la misma prenda.
            $procesos = (clone $baseQuery)
                ->where('prenda_pedido_id', $prendaId)
                ->where('numero_recibo', $numeroReciboCostura)
                ->orderBy('created_at', 'asc')
                ->get();

            // Fallback legacy: usar prenda solo cuando NO se está forzando un recibo objetivo.
            if ($procesos->isEmpty() && !$forzarNumeroRecibo) {
                $procesos = (clone $baseQuery)
                    ->where('prenda_pedido_id', $prendaId)
                    ->orderBy('created_at', 'asc')
                    ->get();
            }
        } else {
            $procesos = (clone $baseQuery)
                ->where('prenda_pedido_id', $prendaId)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        \Log::info('[GetSeguimientoPorPrendaUseCase::obtenerYCalcularProcesos] Procesos encontrados: ' . $procesos->count(), [
            'count' => $procesos->count(),
            'procesos_areas' => $procesos->pluck('proceso')->unique()->toArray()
        ]);

        $completadosPorArea = [];
        if ($reciboCosturaId) {
            $completadosPorArea = $this->consecutivosRepository->obtenerFechasCompletadoPorArea($reciboCosturaId);
        }

        // Obtener fecha del primer proceso (será la fecha_fin de Insumos)
        $fechaPrimerProceso = null;
        if ($procesos->count() > 0) {
            $fechaPrimerProceso = $procesos->first()->created_at;
        }

        $procesosCalculados = [];
        foreach ($procesos as $index => $proceso) {
            $siguienteProceso = $procesos->get($index + 1);

            $clone = clone $proceso;
            // IMPORTANTE: Usar SOLO created_at, no la columna fecha_inicio que está mal
            $clone->fecha_inicio = $proceso->created_at;
            $clone->fecha_fin = $siguienteProceso ? $siguienteProceso->created_at : null;

            $fechaCompletado = $completadosPorArea[strtolower(trim((string) $proceso->proceso))] ?? null;
            
            // Agregar fecha_completado al objeto para que el frontend lo tenga disponible
            $clone->fecha_completado = $fechaCompletado;

            $clone->duraciones = $this->calcularDuracionesArea(
                $proceso->created_at,
                $proceso->fecha_de_asignacion_encargado,
                $fechaCompletado,
                $siguienteProceso ? $siguienteProceso->created_at : null
            );

            $procesosCalculados[] = $clone;
        }

        return [
            'procesos' => $procesosCalculados,
            'fecha_primer_proceso' => $fechaPrimerProceso,
        ];
    }

    /**
     * Agrupar procesos por área
     */
    private function agruparProcesosPorArea(array $procesos): array
    {
        \Log::info('[agruparProcesosPorArea] Agrupando ' . count($procesos) . ' procesos', [
            'count' => count($procesos),
            'areas' => array_map(fn($p) => $p->proceso ?? 'unknown', $procesos)
        ]);

        $resultado = [];

        foreach ($procesos as $proceso) {
            // Compatibilidad: aceptar encargado guardado como nombre (nuevo) o como ID legacy.
            $encargadoRaw = trim((string) ($proceso->encargado ?? ''));
            $encargadoNombre = '';
            $encargadoValorSalida = $encargadoRaw;
            if ($encargadoRaw !== '') {
                if (ctype_digit($encargadoRaw)) {
                    $encargado = \App\Models\User::find((int) $encargadoRaw);
                    $encargadoNombre = $encargado ? (string) $encargado->name : '';
                    $encargadoValorSalida = $encargadoNombre !== '' ? $encargadoNombre : $encargadoRaw;
                } else {
                    $encargadoNombre = $encargadoRaw;
                }
            }

            $resultado[$proceso->proceso] = [
                'id' => $proceso->id,
                'area' => $proceso->proceso,
                'estado' => $proceso->estado_proceso,
                'encargado' => $encargadoValorSalida,
                'encargado_nombre' => $encargadoNombre,
                'fecha_inicio' => $proceso->fecha_inicio,
                'fecha_fin' => $proceso->fecha_fin,
                'fecha_de_asignacion_encargado' => $proceso->fecha_de_asignacion_encargado,
                'fecha_completado' => $proceso->fecha_completado, // usar el valor asignado desde completadosPorArea
                'duraciones' => $proceso->duraciones,
            ];
        }

        \Log::info('[agruparProcesosPorArea] Resultado agrupado', [
            'areas_keys' => array_keys($resultado),
            'areas_count' => count($resultado)
        ]);

        return $resultado;
    }

    /**
     * Calcular duraciones de un área
     */
    private function calcularDuracionesArea(
        $fechaInicio,
        $fechaAsignacion,
        $fechaCompletado,
        $fechaFin
    ): array {

        // Calcular duración de asignación (desde inicio hasta asignación)
        $duracionAsignacion = null;
        if ($fechaInicio && $fechaAsignacion) {
            $duracionAsignacion = $this->calcularDiasHabilesConAPI($fechaInicio, $fechaAsignacion);
        }

        // Calcular duración en área (desde asignación o inicio hasta completado o fin)
        $duracionEnArea = null;
        if ($fechaInicio) {
            $inicioCalculo = $fechaAsignacion ?: $fechaInicio;
            $finCalculo = $fechaCompletado ?: $fechaFin ?: now();
            $duracionEnArea = $this->calcularDiasHabilesConAPI($inicioCalculo, $finCalculo);
        }

        // Calcular total de días = duracion_asignacion + duracion_en_area
        // (No hacer cálculo independiente para evitar duplicación de lógica)
        $totalDias = null;
        if ($duracionAsignacion !== null && $duracionEnArea !== null) {
            $totalDias = $duracionAsignacion + $duracionEnArea;
        } elseif ($fechaInicio) {
            // Fallback si falta alguna duración individual
            $finCalculo = $fechaCompletado ?: $fechaFin ?: now();
            $totalDias = $this->calcularDiasHabilesConAPI($fechaInicio, $finCalculo);
        }

        return [
            'duracion_asignacion' => $duracionAsignacion,
            'duracion_en_area_dias' => $duracionEnArea,
            'total_dias_numero' => $totalDias,
            'estado_display' => !empty($fechaCompletado) ? 'Completado' : 'Pendiente',
            'esta_activo_display' => empty($fechaCompletado),
        ];
    }

    /**
     * Inyectar área virtual Insumos
     */
    private function inyectarAreaInsumos(array $seguimientosPorArea, $consecutivos, $fechaPrimerProceso = null): array
    {
        $hasInsumos = false;
        foreach (array_keys($seguimientosPorArea) as $k) {
            if (strtolower(trim($k)) === 'insumos') {
                $hasInsumos = true;
                break;
            }
        }

        if ($hasInsumos) {
            return $seguimientosPorArea;
        }

        $reciboCostura = null;
        foreach ($consecutivos as $c) {
            if (strtoupper(trim($c->tipo_recibo ?? '')) === 'COSTURA' && ($c->activo ?? 0) == 1) {
                $reciboCostura = $c;
                break;
            }
        }

        if (!$reciboCostura || !$reciboCostura->created_at) {
            return $seguimientosPorArea;
        }

        // CORRECCIÓN: Usar directamente created_at del primer proceso
        // NO usar fecha_inicio de Corte que está incorrecta
        $fechaEnvioProduccion = $fechaPrimerProceso;

        $yaEnviado = !empty($fechaEnvioProduccion);

        // Calcular duraciones para Insumos
        $duracionesInsumos = $this->calcularDuracionesArea(
            $reciboCostura->created_at,
            null, // no hay fecha de asignación para insumos
            $fechaEnvioProduccion, // fecha de completado
            $fechaEnvioProduccion  // fecha fin
        );

        $insumosArea = [
            'id' => null,
            'area' => 'Insumos',
            'estado' => $yaEnviado ? 'Enviado a producción' : 'Llegó a insumos',
            'encargado' => '-',
            'fecha_inicio' => $reciboCostura->created_at,
            'fecha_fin' => $fechaEnvioProduccion,
            'esta_activo' => !$yaEnviado,
            'duraciones' => $duracionesInsumos,
        ];

        return ['Insumos' => $insumosArea] + $seguimientosPorArea;
    }

    /**
     * Calcular datos de activación del recibo
     * Retorna las fechas (creación orden, activación recibo) y tiempo transcurrido
     */
    private function calcularDatosActivacionRecibo($consecutivos, $pedidoModel, ?string $pedidoParcialId = null, ?string $numeroReciboObjetivo = null, ?string $tipoReciboObjetivo = null): array
    {
        // Si es recibo parcial: usar fechas de pedidos_parciales.
        if ($pedidoParcialId !== null && trim($pedidoParcialId) !== '' && is_numeric($pedidoParcialId)) {
            $parcial = $this->consecutivosRepository->obtenerParcialPorId((int) $pedidoParcialId);
            if ($parcial) {
                $fechaCreacionParcial = $parcial->created_at
                    ? ($parcial->created_at instanceof Carbon ? $parcial->created_at : Carbon::parse($parcial->created_at))
                    : null;
                $fechaActivacionParcial = $parcial->fecha_activacion
                    ? ($parcial->fecha_activacion instanceof Carbon ? $parcial->fecha_activacion : Carbon::parse($parcial->fecha_activacion))
                    : null;

                $diasHabilesParcial = null;
                if ($fechaCreacionParcial && $fechaActivacionParcial) {
                    $diasHabilesParcial = $this->calcularDiasHabilesConAPI($fechaCreacionParcial, $fechaActivacionParcial);
                }

                return [
                    'fecha_creacion_orden' => $fechaCreacionParcial,
                    'fecha_creacion_orden_formateada' => $fechaCreacionParcial ? $fechaCreacionParcial->format('d/m/Y \a \l\a\s H:i') : null,
                    'fecha_activacion_recibo' => $fechaActivacionParcial,
                    'fecha_activacion_recibo_formateada' => $fechaActivacionParcial ? $fechaActivacionParcial->format('d/m/Y \a \l\a\s H:i') : null,
                    'dias_transcurridos' => $diasHabilesParcial,
                    'dias_transcurridos_texto' => $diasHabilesParcial !== null ? ($diasHabilesParcial === 0 ? '0 días' : "$diasHabilesParcial día" . ($diasHabilesParcial !== 1 ? 's' : '')) : null,
                ];
            }
        }

        $tipoObjetivo = strtoupper(trim((string) ($tipoReciboObjetivo ?: 'COSTURA')));
        $numeroObjetivo = (is_string($numeroReciboObjetivo) && trim($numeroReciboObjetivo) !== '' && is_numeric($numeroReciboObjetivo))
            ? (int) $numeroReciboObjetivo
            : null;

        $reciboObjetivo = null;
        if ($numeroObjetivo !== null) {
            foreach ($consecutivos as $c) {
                $tipo = strtoupper(trim((string) ($c->tipo_recibo ?? '')));
                if ($tipo === $tipoObjetivo && (int) ($c->consecutivo_actual ?? 0) === $numeroObjetivo) {
                    $reciboObjetivo = $c;
                    break;
                }
            }
        }
        if (!$reciboObjetivo) {
            foreach ($consecutivos as $c) {
                $tipo = strtoupper(trim((string) ($c->tipo_recibo ?? '')));
                if ($tipo === $tipoObjetivo && (int) ($c->activo ?? 0) === 1) {
                    $reciboObjetivo = $c;
                    break;
                }
            }
        }
        if (!$reciboObjetivo) {
            foreach ($consecutivos as $c) {
                if (strtoupper(trim((string) ($c->tipo_recibo ?? ''))) === 'COSTURA' && (int) ($c->activo ?? 0) === 1) {
                    $reciboObjetivo = $c;
                    break;
                }
            }
        }

        if (!$reciboObjetivo) {
            return [];
        }

        // Convertir a Carbon si es necesario
        $reciboCreatedAt = $reciboObjetivo->created_at ? ($reciboObjetivo->created_at instanceof Carbon ? $reciboObjetivo->created_at : Carbon::parse($reciboObjetivo->created_at)) : null;
        $fechaCreacionOrden = $pedidoModel->created_at ? ($pedidoModel->created_at instanceof Carbon ? $pedidoModel->created_at : Carbon::parse($pedidoModel->created_at)) : null;

        $diasHabiles = null;
        if ($fechaCreacionOrden && $reciboCreatedAt) {
            $diasHabiles = $this->calcularDiasHabilesConAPI($fechaCreacionOrden, $reciboCreatedAt);
        }

        $datosFormateados = [
            'fecha_creacion_orden' => $fechaCreacionOrden,
            'fecha_creacion_orden_formateada' => $fechaCreacionOrden ? $fechaCreacionOrden->format('d/m/Y \a \l\a\s H:i') : null,
            'fecha_activacion_recibo' => $reciboCreatedAt,
            'fecha_activacion_recibo_formateada' => $reciboCreatedAt ? $reciboCreatedAt->format('d/m/Y \a \l\a\s H:i') : null,
            'dias_transcurridos' => $diasHabiles,
            'dias_transcurridos_texto' => $diasHabiles !== null ? ($diasHabiles === 0 ? '0 días' : "$diasHabiles día" . ($diasHabiles !== 1 ? 's' : '')) : null,
        ];

        \Log::info('[calcularDatosActivacionRecibo] RETORNANDO', $datosFormateados);

        return $datosFormateados;
    }

    /**
     * Calcular dias habiles con cmixin/business-day (sin API externa)
     * @param Carbon|string|null $fechaInicio
     * @param Carbon|string|null $fechaFin
     * @return int|null
     */
    private function calcularDiasHabilesConAPI($fechaInicio = null, $fechaFin = null): ?int
    {
        if (!$fechaInicio || !$fechaFin) {
            return null;
        }

        $inicio = $fechaInicio instanceof Carbon ? $fechaInicio : Carbon::parse($fechaInicio);
        $fin = $fechaFin instanceof Carbon ? $fechaFin : Carbon::parse($fechaFin);

        if ($inicio->format('Y-m-d') === $fin->format('Y-m-d') || $fin < $inicio) {
            return 0;
        }

        try {
            $diasHabiles = 0;
            $actual = $inicio->copy()->startOfDay();
            $fin = $fin->copy()->startOfDay();

            while ($actual->lte($fin)) {
                if ($actual->isBusinessDay()) {
                    $diasHabiles++;
                }
                $actual->addDay();
            }

            // Mantener comportamiento historico del modulo
            return max(0, $diasHabiles - 1);
        } catch (\Exception $e) {
            Log::error('[GetSeguimientoPorPrendaUseCase] Error calculando dias habiles', [
                'error' => $e->getMessage(),
                'fecha_inicio' => $inicio->format('Y-m-d'),
                'fecha_fin' => $fin->format('Y-m-d'),
            ]);
            return null;
        }
    }

    /**
     * Resolver recibo principal del pedido
     */
    private function resolveReciboPrincipal(array $prendasConSeguimiento): string
    {
        foreach ($prendasConSeguimiento as $prenda) {
            foreach ($prenda['consecutivos'] as $c) {
                // Cast defensivo: pueden ser stdClass o array
                $cArray = is_array($c) ? $c : (array) $c;

                if (($cArray['tipo_recibo'] ?? null) === 'COSTURA' && ($cArray['activo'] ?? 0) == 1) {
                    return (string) ($cArray['consecutivo_actual'] ?? '-');
                }
            }
        }
        return '-';
    }

    /**
     * Obtener el area desde la tabla consecutivos_recibos_pedidos
     * Retorna directamente el area del registro
     */
    private function obtenerAreaMasReciente($consecutivos): ?string
    {
        if (!$consecutivos || count($consecutivos) === 0) {
            return null;
        }

        // IMPORTANTE: Tomar el área del recibo COSTURA (el que tiene consecutivo_actual)
        // NO del primer consecutivo en general, porque puede haber múltiples tipos de recibos
        // (COSTURA, ESTAMPADO, BORDADO, etc.) cada uno con su propia área
        $reciboCostura = null;
        foreach ($consecutivos as $c) {
            $tipoRecibo = is_array($c) ? ($c['tipo_recibo'] ?? null) : ($c->tipo_recibo ?? null);
            if ($tipoRecibo === 'COSTURA') {
                $reciboCostura = $c;
                break;
            }
        }

        // Si no hay COSTURA, tomar el primer consecutivo disponible
        $consecutivoParaArea = $reciboCostura ?? $consecutivos->first();
        
        if (!$consecutivoParaArea) {
            return null;
        }

        // Convertir a array si es stdClass
        $arrayData = is_array($consecutivoParaArea) ? $consecutivoParaArea : (array) $consecutivoParaArea;
        $area = $arrayData['area'] ?? null;
        
        return !empty($area) ? trim($area) : null;
    }

    /**
     * Obtener recibos especiales (BORDADO, ESTAMPADO, DTF, SUBLIMADO)
     * para mostrar botones adicionales en la UI
     */
    private function obtenerRecibosEspeciales($consecutivos, int $prendaId, $procesosPrenda = null): array
    {
        if (!$consecutivos || count($consecutivos) === 0) {
            \Log::info('[obtenerRecibosEspeciales] SIN CONSECUTIVOS', ['prenda_id' => $prendaId]);
            return [];
        }

        // DEBUG: Log iniciales
        $consecutivosDebug = [];
        foreach ($consecutivos as $c) {
            $arrayData = is_array($c) ? $c : (array) $c;
            $consecutivosDebug[] = [
                'tipo_recibo' => $arrayData['tipo_recibo'] ?? null,
                'activo' => $arrayData['activo'] ?? null,
            ];
        }
        \Log::info('[obtenerRecibosEspeciales] Iniciando con consecutivos', [
            'prenda_id' => $prendaId,
            'total_consecutivos' => count($consecutivosDebug),
            'consecutivos' => $consecutivosDebug,
        ]);

        $tiposEspeciales = ['BORDADO', 'ESTAMPADO', 'DTF', 'SUBLIMADO', 'REFLECTIVO'];
        $mapeoTipoProcesoId = [
            'BORDADO' => 2,
            'ESTAMPADO' => 3,
            'DTF' => 4,
            'SUBLIMADO' => 5,
        ];
        
        // Crear un mapa de tipo_proceso_id -> proceso_prenda_detalle_id
        $procesoIdMap = [];
        if ($procesosPrenda && is_iterable($procesosPrenda)) {
            foreach ($procesosPrenda as $proceso) {
                $tipoProcesoId = $proceso->tipo_proceso_id ?? null;
                if ($tipoProcesoId) {
                    $procesoIdMap[$tipoProcesoId] = $proceso->id;
                }
            }
        }
        
        $recibosEspeciales = [];

        foreach ($consecutivos as $consecutivo) {
            // Convertir a array si es stdClass
            $arrayData = is_array($consecutivo) ? $consecutivo : (array) $consecutivo;
            $tipoRecibo = strtoupper(trim($arrayData['tipo_recibo'] ?? ''));
            
            if (in_array($tipoRecibo, $tiposEspeciales)) {
                // Iniciar con el área del consecutivo como fallback
                $area = $arrayData['area'] ?? null;
                
                // Para BORDADO, ESTAMPADO, DTF, SUBLIMADO, intentar obtener área desde prenda_areas_logo_pedido
                if (isset($mapeoTipoProcesoId[$tipoRecibo])) {
                    $tipoProcesoId = $mapeoTipoProcesoId[$tipoRecibo];
                    
                    // Usar el ID del proceso desde el mapa si está disponible
                    if (isset($procesoIdMap[$tipoProcesoId])) {
                        $procesoPrendaDetalleId = $procesoIdMap[$tipoProcesoId];
                        
                        // Obtener el área más reciente de prenda_areas_logo_pedido
                        $areaRegistro = \DB::table('prenda_areas_logo_pedido')
                            ->where('prenda_pedido_id', $prendaId)
                            ->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId)
                            ->orderByDesc('created_at')
                            ->first();
                        
                        if ($areaRegistro && !empty($areaRegistro->area)) {
                            $area = $areaRegistro->area;
                        }
                    }
                }
                
                \Log::debug('[obtenerRecibosEspeciales] Recibo especial procesado', [
                    'tipo_recibo' => $tipoRecibo,
                    'prenda_id' => $prendaId,
                    'area_final' => $area,
                    'consecutivo' => $arrayData['consecutivo_actual'] ?? null,
                ]);
                
                $recibosEspeciales[] = [
                    'id' => $arrayData['id'] ?? null,
                    'tipo_recibo' => $tipoRecibo,
                    'consecutivo' => $arrayData['consecutivo_actual'] ?? null,
                    'area' => $area,
                    'estado' => $arrayData['estado'] ?? null,
                    'activo' => $arrayData['activo'] ?? 0,
                ];
            }
        }

        return $recibosEspeciales;
    }
}
