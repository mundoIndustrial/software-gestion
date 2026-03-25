<?php

namespace App\Application\UseCases\RegistroOrden;

use App\Infrastructure\Repositories\PedidoProduccionRepository;
use App\Infrastructure\Repositories\ConsecutivosRecibosRepository;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Services\CalculadorDiasService;

/**
 * GetSeguimientoPorPrendaUseCase
 * 
 * Orquesta la obtención del seguimiento de prendas por pedido
 * Cumple DDD: Application Layer - UseCase
 * Delega queries a Repositories, lógica de negocio aquí
 */
class GetSeguimientoPorPrendaUseCase
{
    private PedidoProduccionRepository $pedidoRepository;
    private ConsecutivosRecibosRepository $consecutivosRepository;

    public function __construct(
        PedidoProduccionRepository $pedidoRepository,
        ConsecutivosRecibosRepository $consecutivosRepository
    ) {
        $this->pedidoRepository = $pedidoRepository;
        $this->consecutivosRepository = $consecutivosRepository;
    }

    /**
     * Ejecutar el use case
     * GET /registros/{pedido}/seguimiento-prenda
     */
    public function execute(string $pedido): array
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

            $prendas = $this->obtenerPrendasConSeguimiento($pedidoId, $pedidoModel);

            return [
                'success' => true,
                'pedido' => [
                    'id' => $pedidoModel->id,
                    'numero_pedido' => $pedidoModel->numero_pedido,
                    'cliente' => $pedidoModel->cliente,
                    'created_at' => $pedidoModel->created_at ?? $pedidoModel->created_at,
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
    private function obtenerPrendasConSeguimiento(int $pedidoId, $pedidoModel): array
    {
        $prendasDB = PrendaPedido::where('pedido_produccion_id', $pedidoId)
            ->with(['variantes', 'procesos.tipoProceso', 'tallas'])
            ->get();

        $prendasConSeguimiento = [];

        foreach ($prendasDB as $prenda) {
            $seguimiento = $this->construirSeguimientoPrenda($prenda, $pedidoId, $pedidoModel);
            $prendasConSeguimiento[] = $seguimiento;
        }

        return $prendasConSeguimiento;
    }

    /**
     * Construir objeto de seguimiento para una prenda
     */
    private function construirSeguimientoPrenda($prenda, int $pedidoId, $pedidoModel): array
    {
        $consecutivos = $this->consecutivosRepository->obtenerTodosPorPrenda($prenda->id, $pedidoId);

        $numeroReciboCostura = null;
        $reciboCosturaId = null;
        foreach ($consecutivos as $c) {
            if (($c->tipo_recibo ?? null) === 'COSTURA' && !empty($c->consecutivo_actual)) {
                $numeroReciboCostura = (int) $c->consecutivo_actual;
                $reciboCosturaId = $c->id ?? null;
                break;
            }
        }

        $procesosSeguimiento = $this->obtenerYCalcularProcesos(
            $pedidoModel->numero_pedido,
            $prenda->id,
            $numeroReciboCostura,
            $reciboCosturaId
        );

        $seguimientosPorArea = $this->agruparProcesosPorArea($procesosSeguimiento);

        $seguimientosPorArea = $this->inyectarAreaInsumos($seguimientosPorArea, $consecutivos, $pedidoModel);

        $datosActivacion = $this->calcularDatosActivacionRecibo($consecutivos, $pedidoModel);

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

        return [
            'id' => $prenda->id,
            'nombre_prenda' => $prenda->nombre_prenda,
            'descripcion' => $prenda->descripcion,
            'cantidad' => $prenda->cantidad_total,
            'cantidad_talla' => $cantidadTalla,
            'de_bodega' => $prenda->de_bodega,
            'seguimientos_por_area' => $seguimientosPorArea,
            'procesos' => $procesosArray,
            'consecutivos' => $consecutivos->toArray(),
        ];
    }

    /**
     * Obtener procesos y calcular duraciones
     */
    private function obtenerYCalcularProcesos(
        string $numeroPedido,
        int $prendaId,
        ?int $numeroReciboCostura,
        ?int $reciboCosturaId
    ): array {
        $procesos = ProcesoPrenda::where('numero_pedido', $numeroPedido)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($prendaId, $numeroReciboCostura) {
                $q->where('prenda_pedido_id', $prendaId);
                if ($numeroReciboCostura) {
                    $q->orWhere('numero_recibo', $numeroReciboCostura);
                }
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Obtener fechas de completado por área
        $completadosPorArea = [];
        if ($reciboCosturaId) {
            $completadosPorArea = $this->consecutivosRepository->obtenerFechasCompletadoPorArea($reciboCosturaId);
        }

        $procesosCalculados = [];
        foreach ($procesos as $index => $proceso) {
            $siguienteProceso = $procesos->get($index + 1);

            $clone = clone $proceso;
            $clone->fecha_inicio = $proceso->created_at;
            $clone->fecha_fin = $siguienteProceso ? $siguienteProceso->created_at : null;

            $metadata = $this->resolveAreaMetadata($proceso->proceso);
            $fechaCompletado = $completadosPorArea[strtolower(trim((string) $proceso->proceso))] ?? null;

            $clone->duraciones = $this->calcularDuracionesArea(
                $proceso->proceso,
                $proceso->created_at,
                $proceso->fecha_de_asignacion_encargado,
                $fechaCompletado,
                $siguienteProceso ? $siguienteProceso->created_at : null,
                $proceso->estado_proceso
            );

            $procesosCalculados[] = $clone;
        }

        return $procesosCalculados;
    }

    /**
     * Agrupar procesos por área
     */
    private function agruparProcesosPorArea(array $procesos): array
    {
        $resultado = [];

        foreach ($procesos as $proceso) {
            $resultado[$proceso->proceso] = [
                'id' => $proceso->id,
                'area' => $proceso->proceso,
                'estado' => $proceso->estado_proceso,
                'encargado' => $proceso->encargado,
                'fecha_inicio' => $proceso->fecha_inicio,
                'fecha_fin' => $proceso->fecha_fin,
                'duraciones' => $proceso->duraciones,
            ];
        }

        return $resultado;
    }

    /**
     * Resolver metadata de un área
     */
    private function resolveAreaMetadata(string $area): array
    {
        $areaLower = strtolower(trim($area));

        return [
            'isInsumos' => $areaLower === 'insumos',
            'isCorte' => str_contains($areaLower, 'corte'),
            'isCostura' => str_contains($areaLower, 'costura'),
            'isControlCalidad' => str_contains($areaLower, 'control') && str_contains($areaLower, 'calidad'),
            'needsEncargado' => (
                str_contains($areaLower, 'corte') ||
                str_contains($areaLower, 'costura') ||
                (str_contains($areaLower, 'control') && str_contains($areaLower, 'calidad'))
            ),
        ];
    }

    /**
     * Calcular duraciones de un área
     */
    private function calcularDuracionesArea(
        string $area,
        $fechaInicio,
        $fechaAsignacion,
        $fechaCompletado,
        $fechaFin,
        string $estado
    ): array {
        $metadata = $this->resolveAreaMetadata($area);

        $duracionEnArea = null;
        if ($fechaInicio) {
            $inicioCalculo = $fechaAsignacion ?: $fechaInicio;
            $finCalculo = $fechaCompletado ?: $fechaFin ?: now();
            $duracionEnArea = CalculadorDiasService::calcularDiasHabiles($inicioCalculo, $finCalculo);
        }

        return [
            'duracion_en_area_dias' => $duracionEnArea,
            'total_dias_numero' => $duracionEnArea,
            'estado_display' => !empty($fechaCompletado) ? 'Completado' : 'Pendiente',
            'esta_activo_display' => empty($fechaCompletado),
        ];
    }

    /**
     * Inyectar área virtual Insumos
     */
    private function inyectarAreaInsumos(array $seguimientosPorArea, $consecutivos, $pedidoModel): array
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

        $fechaEnvioProduccion = null;
        foreach (array_keys($seguimientosPorArea) as $k) {
            if (str_contains(strtolower($k), 'corte')) {
                $fechaEnvioProduccion = $seguimientosPorArea[$k]['fecha_inicio'] ?? null;
                break;
            }
        }

        $yaEnviado = !empty($fechaEnvioProduccion);

        $insumosArea = [
            'id' => null,
            'area' => 'Insumos',
            'estado' => $yaEnviado ? 'Enviado a producción' : 'Llegó a insumos',
            'encargado' => '-',
            'fecha_inicio' => $reciboCostura->created_at,
            'fecha_fin' => $fechaEnvioProduccion,
            'esta_activo' => !$yaEnviado,
        ];

        return ['Insumos' => $insumosArea] + $seguimientosPorArea;
    }

    /**
     * Calcular datos de activación del recibo
     */
    private function calcularDatosActivacionRecibo($consecutivos, $pedidoModel): array
    {
        $reciboCostura = null;
        foreach ($consecutivos as $c) {
            if (strtoupper(trim($c->tipo_recibo ?? '')) === 'COSTURA' && ($c->activo ?? 0) == 1) {
                $reciboCostura = $c;
                break;
            }
        }

        if (!$reciboCostura) {
            return [];
        }

        $reciboCreatedAt = $reciboCostura->created_at ?? null;
        $fechaCreacionOrden = $pedidoModel->created_at ?? null;

        $diasHabiles = null;
        if ($fechaCreacionOrden && $reciboCreatedAt) {
            $diasHabiles = CalculadorDiasService::calcularDiasHabiles($fechaCreacionOrden, $reciboCreatedAt);
        }

        return [
            'dias_transcurridos' => $diasHabiles,
            'fecha_activacion' => $reciboCreatedAt,
        ];
    }

    /**
     * Resolver recibo principal del pedido
     */
    private function resolveReciboPrincipal(array $prendasConSeguimiento): string
    {
        foreach ($prendasConSeguimiento as $prenda) {
            foreach ($prenda['consecutivos'] as $c) {
                if (($c['tipo_recibo'] ?? null) === 'COSTURA' && ($c['activo'] ?? 0) == 1) {
                    return (string) ($c['consecutivo_actual'] ?? '-');
                }
            }
        }
        return '-';
    }
}
