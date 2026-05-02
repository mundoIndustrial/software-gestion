<?php

namespace App\Infrastructure\Http\Controllers\Cartera;

use App\Http\Controllers\Controller;
use App\Application\Shared\Services\PerformanceLogger;
use App\Application\Pedidos\UseCases\Cartera\ObtenerPedidosPendientesUseCase;
use App\Application\Pedidos\UseCases\Cartera\ObtenerPedidosAprobadosUseCase;
use App\Application\Pedidos\UseCases\Cartera\ObtenerPedidosRechazadosUseCase;
use App\Application\Pedidos\UseCases\Cartera\ObtenerPedidosAnuladosUseCase;
use App\Application\Pedidos\UseCases\Cartera\ObtenerOpcionesFiltroUseCase;
use App\Application\Pedidos\UseCases\Cartera\AprobarPedidoUseCase;
use App\Application\Pedidos\UseCases\Cartera\RechazarPedidoUseCase;
use App\Application\Pedidos\UseCases\Cartera\ObtenerDatosFacturaUseCase;
use Illuminate\Http\Request;

class CarteraPedidosController extends Controller
{
    /**
     * Obtener pedidos pendientes de cartera con paginación y filtros
     */
    public function obtenerPedidos(Request $request, ObtenerPedidosPendientesUseCase $useCase)
    {
        // Iniciar logging de performance
        PerformanceLogger::startRequest('GET /api/cartera/pedidos');
        PerformanceLogger::marker('CONTROLLER_START', [
            'page' => $request->get('page', 1),
            'per_page' => $request->get('per_page', 15),
            'has_search' => !empty($request->get('search')),
            'has_filters' => !empty($request->get('cliente')) || !empty($request->get('fecha_desde')),
        ]);

        try {
            $filtros = [
                'page' => max(1, (int) $request->get('page', 1)),
                'per_page' => max(1, min((int) $request->get('per_page', 15), 100)),
                'search' => $request->get('search', ''),
                'cliente' => $request->get('cliente', ''),
                'fecha_desde' => $request->get('fecha_desde', ''),
                'fecha_hasta' => $request->get('fecha_hasta', ''),
                'sort_by' => $request->get('sort_by', 'fecha'),
                'sort_order' => in_array(strtolower($request->get('sort_order', 'desc')), ['asc', 'desc']) ? strtolower($request->get('sort_order', 'desc')) : 'desc',
            ];

            PerformanceLogger::marker('PARAMS_PREPARED');

            $resultado = $useCase->execute($filtros);

            PerformanceLogger::marker('USE_CASE_EXECUTED', [
                'success' => $resultado['success'] ?? false,
                'error' => $resultado['message'] ?? null,
            ]);

            if (!$resultado['success']) {
                PerformanceLogger::endRequest(500);
                return response()->json([
                    'success' => false,
                    'message' => $resultado['message'] ?? 'Error al obtener pedidos'
                ], 500);
            }

            $data = $resultado['data'];
            $pedidos = $data['pedidos']->map(function($pedido) {
                return [
                    'id' => $pedido->id,
                    'numero' => $pedido->numero_pedido,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente_nombre' => $pedido->cliente,
                    'cliente' => $pedido->cliente,
                    'asesor_nombre' => $pedido->asesora->name ?? null,
                    'estado' => $pedido->estado,
                    'created_at' => $pedido->created_at ?? $pedido->created_at
                ];
            });

            PerformanceLogger::marker('DATA_MAPPED', [
                'pedidos_count' => count($pedidos),
            ]);

            $response = [
                'success' => true,
                'data' => $pedidos,
                'pagination' => [
                    'page' => $data['page'],
                    'per_page' => $data['per_page'],
                    'total' => $data['total'],
                    'last_page' => $data['last_page'],
                    'from' => ($data['page'] - 1) * $data['per_page'] + 1,
                    'to' => min($data['page'] * $data['per_page'], $data['total'])
                ]
            ];

            // Agregar timing si está disponible
            if (config('app.debug')) {
                $perf = PerformanceLogger::getSummary();
                $response['_timing'] = $perf;
            }

            PerformanceLogger::endRequest(200);

            return response()->json($response);
        } catch (\Exception $e) {
            PerformanceLogger::endRequest(500);
            throw $e;
        }
    }

    /**
     * Aprobar pedido
     */
    public function aprobarPedido($id, Request $request, AprobarPedidoUseCase $useCase)
    {
        $resultado = $useCase->execute((int) $id);

        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'message' => $resultado['message'] ?? 'Error al aprobar pedido'
            ], 500);
        }

        // Intentar broadcast (separado de la lógica de negocio)
        if ($resultado['pedido']) {
            try {
                broadcast(new \App\Events\OrdenUpdated($resultado['pedido'], 'created', ['numero_pedido', 'estado']));
            } catch (\Exception $e) {
                \Log::warning('[CARTERA] Broadcast falló (no crítico)', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $resultado['message'],
            'numero_pedido' => $resultado['numero_pedido']
        ]);
    }

    /**
     * Rechazar pedido
     */
    public function rechazarPedido($id, Request $request, RechazarPedidoUseCase $useCase)
    {
        $request->validate([
            'motivo' => 'required|string|max:2000'
        ]);

        $resultado = $useCase->execute((int) $id, $request->get('motivo'));

        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'message' => $resultado['message'] ?? 'Error al rechazar pedido'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $resultado['message'],
            'numero_pedido' => $resultado['numero_pedido']
        ]);
    }

    /**
     * Obtener pedidos aprobados por cartera (PENDIENTE_SUPERVISOR)
     */
    public function obtenerAprobados(Request $request, ObtenerPedidosAprobadosUseCase $useCase)
    {
        $filtros = [
            'page' => max(1, (int) $request->get('page', 1)),
            'per_page' => max(1, min((int) $request->get('per_page', 15), 100)),
            'search' => $request->get('search', ''),
            'cliente' => $request->get('cliente', ''),
            'fecha_desde' => $request->get('fecha_desde', ''),
            'fecha_hasta' => $request->get('fecha_hasta', ''),
            'sort_by' => $request->get('sort_by', 'fecha'),
            'sort_order' => in_array(strtolower($request->get('sort_order', 'desc')), ['asc', 'desc']) ? strtolower($request->get('sort_order', 'desc')) : 'desc',
        ];

        $resultado = $useCase->execute($filtros);

        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'message' => $resultado['message'] ?? 'Error al obtener pedidos aprobados'
            ], 500);
        }

        $data = $resultado['data'];
        $pedidos = $data['pedidos']->map(function($pedido) {
            return [
                'id' => $pedido->id,
                'numero' => $pedido->numero_pedido,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente_nombre' => $pedido->cliente,
                'cliente' => $pedido->cliente,
                'estado' => $pedido->estado,
                'created_at' => $pedido->aprobado_por_cartera_en ?? $pedido->created_at,
                'aprobado_por_cartera_en' => $pedido->aprobado_por_cartera_en,
                'aprobado_por_usuario_cartera' => $pedido->aprobado_por_usuario_cartera
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $pedidos,
            'pagination' => [
                'page' => $data['page'],
                'per_page' => $data['per_page'],
                'total' => $data['total'],
                'last_page' => $data['last_page'],
                'from' => ($data['page'] - 1) * $data['per_page'] + 1,
                'to' => min($data['page'] * $data['per_page'], $data['total'])
            ]
        ]);
    }

    /**
     * Obtener pedidos rechazados por cartera (RECHAZADO_CARTERA)
     */
    public function obtenerRechazados(Request $request, ObtenerPedidosRechazadosUseCase $useCase)
    {
        $filtros = [
            'page' => max(1, (int) $request->get('page', 1)),
            'per_page' => max(1, min((int) $request->get('per_page', 15), 100)),
            'search' => $request->get('search', ''),
            'cliente' => $request->get('cliente', ''),
            'fecha_desde' => $request->get('fecha_desde', ''),
            'fecha_hasta' => $request->get('fecha_hasta', ''),
            'sort_by' => $request->get('sort_by', 'fecha'),
            'sort_order' => in_array(strtolower($request->get('sort_order', 'desc')), ['asc', 'desc']) ? strtolower($request->get('sort_order', 'desc')) : 'desc',
        ];

        $resultado = $useCase->execute($filtros);

        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'message' => $resultado['message'] ?? 'Error al obtener pedidos rechazados'
            ], 500);
        }

        $data = $resultado['data'];
        $pedidos = $data['pedidos']->map(function($pedido) {
            return [
                'id' => $pedido->id,
                'numero' => $pedido->numero_pedido,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente_nombre' => $pedido->cliente,
                'cliente' => $pedido->cliente,
                'estado' => $pedido->estado,
                'created_at' => $pedido->rechazado_por_cartera_en ?? $pedido->created_at,
                'rechazado_por_cartera_en' => $pedido->rechazado_por_cartera_en,
                'rechazado_por_usuario_cartera' => $pedido->rechazado_por_usuario_cartera,
                'motivo_rechazo_cartera' => $pedido->motivo_rechazo_cartera
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $pedidos,
            'pagination' => [
                'page' => $data['page'],
                'per_page' => $data['per_page'],
                'total' => $data['total'],
                'last_page' => $data['last_page'],
                'from' => ($data['page'] - 1) * $data['per_page'] + 1,
                'to' => min($data['page'] * $data['per_page'], $data['total'])
            ]
        ]);
    }

    /**
     * Obtener pedidos anulados (Anulada)
     */
    public function obtenerAnulados(Request $request, ObtenerPedidosAnuladosUseCase $useCase)
    {
        $filtros = [
            'page' => max(1, (int) $request->get('page', 1)),
            'per_page' => max(1, min((int) $request->get('per_page', 15), 100)),
            'search' => $request->get('search', ''),
            'cliente' => $request->get('cliente', ''),
            'fecha_desde' => $request->get('fecha_desde', ''),
            'fecha_hasta' => $request->get('fecha_hasta', ''),
            'sort_by' => $request->get('sort_by', 'fecha'),
            'sort_order' => in_array(strtolower($request->get('sort_order', 'desc')), ['asc', 'desc']) ? strtolower($request->get('sort_order', 'desc')) : 'desc',
        ];

        $resultado = $useCase->execute($filtros);

        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'message' => $resultado['message'] ?? 'Error al obtener pedidos anulados'
            ], 500);
        }

        $data = $resultado['data'];
        $pedidos = $data['pedidos']->map(function($pedido) {
            return [
                'id' => $pedido->id,
                'numero' => $pedido->numero_pedido,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente_nombre' => $pedido->cliente,
                'cliente' => $pedido->cliente,
                'estado' => $pedido->estado,
                'created_at' => $pedido->updated_at,
                'updated_at' => $pedido->updated_at,
                'novedades' => $pedido->novedades
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $pedidos,
            'pagination' => [
                'page' => $data['page'],
                'per_page' => $data['per_page'],
                'total' => $data['total'],
                'last_page' => $data['last_page'],
                'from' => ($data['page'] - 1) * $data['per_page'] + 1,
                'to' => min($data['page'] * $data['per_page'], $data['total'])
            ]
        ]);
    }

    /**
     * Obtener opciones de filtro (clientes únicos y fechas)
     */
    public function obtenerOpcionesFiltro(Request $request, ObtenerOpcionesFiltroUseCase $useCase)
    {
        $resultado = $useCase->execute();

        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'message' => $resultado['message'] ?? 'Error al obtener opciones de filtro'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'clientes' => $resultado['clientes'],
            'fechas' => $resultado['fechas']
        ]);
    }

    /**
     * Obtener datos de factura para mostrar en modal
     */
    public function obtenerDatosFactura($id, ObtenerDatosFacturaUseCase $useCase)
    {
        $resultado = $useCase->execute((int) $id);

        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'message' => $resultado['message'] ?? 'Error al obtener datos'
            ], 500);
        }

        $datos = $resultado['data'];
        
        // Para el rol cartera, no mostrar sección de EPP
        $datos['mostrarEPP'] = !auth()->user()->hasRole('cartera');

        return response()->json($datos);
    }
}
