<?php

namespace App\Application\UseCases\RegistroOrden;

use App\Application\Pedidos\Services\PrendaPedidoQuantityCalculator;
use App\Infrastructure\Repositories\PedidoProduccionTrackingRepository;
use App\Infrastructure\Repositories\ConsecutivosRecibosRepository;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Services\CalculadorDiasService;

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

        $seguimientosPorArea = $this->inyectarAreaInsumos($seguimientosPorArea, $consecutivos);

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
            'cantidad' => $this->prendaQuantityCalculator->calculate($prenda),
            'cantidad_talla' => $cantidadTalla,
            'de_bodega' => $prenda->de_bodega,
            'seguimientos_por_area' => $seguimientosPorArea,
            'procesos' => $procesosArray,
            'consecutivos' => $consecutivos->toArray(),
            'datos_activacion' => $datosActivacion,
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
        \Log::info('[GetSeguimientoPorPrendaUseCase::obtenerYCalcularProcesos] Buscando procesos', [
            'numero_pedido' => $numeroPedido,
            'prenda_pedido_id' => $prendaId,
            'numero_recibo_costura' => $numeroReciboCostura,
            'recibo_costura_id' => $reciboCosturaId
        ]);

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

        \Log::info('[GetSeguimientoPorPrendaUseCase::obtenerYCalcularProcesos] Procesos encontrados: ' . $procesos->count(), [
            'count' => $procesos->count(),
            'procesos_areas' => $procesos->pluck('proceso')->unique()->toArray()
        ]);

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

        return $procesosCalculados;
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
            // Obtener nombre del encargado si existe
            $encargadoNombre = '';
            if (!empty($proceso->encargado)) {
                $encargado = \App\Models\User::find($proceso->encargado);
                $encargadoNombre = $encargado ? $encargado->name : '';
            }

            $resultado[$proceso->proceso] = [
                'id' => $proceso->id,
                'area' => $proceso->proceso,
                'estado' => $proceso->estado_proceso,
                'encargado' => $proceso->encargado, // ID
                'encargado_nombre' => $encargadoNombre, // Nombre
                'fecha_inicio' => $proceso->fecha_inicio,
                'fecha_fin' => $proceso->fecha_fin,
                'fecha_de_asignacion_encargado' => $proceso->fecha_de_asignacion_encargado,
                'fecha_completado' => null, // se calcula desde completadosPorArea si existe
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
            $duracionAsignacion = CalculadorDiasService::calcularDiasHabiles($fechaInicio, $fechaAsignacion);
        }

        // Calcular duración en área (desde asignación o inicio hasta completado o fin)
        $duracionEnArea = null;
        if ($fechaInicio) {
            $inicioCalculo = $fechaAsignacion ?: $fechaInicio;
            $finCalculo = $fechaCompletado ?: $fechaFin ?: now();
            $duracionEnArea = CalculadorDiasService::calcularDiasHabiles($inicioCalculo, $finCalculo);
        }

        // Calcular total de días (desde inicio hasta completado/fin)
        $totalDias = null;
        if ($fechaInicio) {
            $finCalculo = $fechaCompletado ?: $fechaFin ?: now();
            $totalDias = CalculadorDiasService::calcularDiasHabiles($fechaInicio, $finCalculo);
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
    private function inyectarAreaInsumos(array $seguimientosPorArea, $consecutivos): array
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
                // Cast defensivo: pueden ser stdClass o array
                $cArray = is_array($c) ? $c : (array) $c;
                
                if (($cArray['tipo_recibo'] ?? null) === 'COSTURA' && ($cArray['activo'] ?? 0) == 1) {
                    return (string) ($cArray['consecutivo_actual'] ?? '-');
                }
            }
        }
        return '-';
    }
}
