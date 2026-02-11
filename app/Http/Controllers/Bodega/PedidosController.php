<?php

namespace App\Http\Controllers\Bodega;

use App\Http\Controllers\Controller;
use App\Models\ReciboPrenda;
use App\Models\PedidoProduccion;
use App\Models\PedidoAuditoria;
use App\Application\Bodega\Services\BodegaPedidoService;
use App\Application\Bodega\Services\BodegaRoleService;
use App\Application\Bodega\Services\BodegaNotaService;
use App\Application\Bodega\Services\BodegaAuditoriaService;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\Despacho\UseCases\ObtenerFilasDespachoUseCase;
use App\Application\Bodega\CQRS\CQRSManager;
use App\Application\Bodega\CQRS\Commands\EntregarPedidoCommand;
use App\Application\Bodega\CQRS\Commands\ActualizarEstadoPedidoCommand;
use App\Application\Bodega\CQRS\Queries\ObtenerPedidosPorAreaQuery;
use App\Application\Bodega\CQRS\Queries\ObtenerEstadisticasPedidosQuery;
use App\Domain\Bodega\ValueObjects\AreaBodega;
use App\Domain\Bodega\ValueObjects\EstadoPedido;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PedidosController extends Controller
{
    public function __construct(
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private ObtenerFilasDespachoUseCase $obtenerFilas,
        private PedidoProduccionRepository $pedidoRepository,
        private BodegaPedidoService $bodegaPedidoService,
        private BodegaRoleService $roleService,
        private BodegaNotaService $notaService,
        private BodegaAuditoriaService $auditoriaService,
        private CQRSManager $cqrsManager,
    ) {}

    /**
     * Mostrar lista de pedidos para bodeguero
     */
    public function index(Request $request)
    {
        try {
            $datos = $this->bodegaPedidoService->obtenerPedidosPaginados($request);
            
            if ($datos['view_type'] === 'details') {
                $usuario = auth()->user();
                $rolesDelUsuario = $usuario->getRoleNames()->toArray();
                $esReadOnly = $this->roleService->esReadOnly($rolesDelUsuario);
                
                $viewName = $esReadOnly ? 'bodega.pedidos-readonly' : 'bodega.pedidos';
                
                return view($viewName, [
                    'pedidosAgrupados' => $datos['pedidos_agrupados'] ?? [],
                    'asesores' => $datos['asesores'] ?? [],
                    'paginacion' => $datos['pagination']['paginacion_obj'] ?? null,
                    'totalPedidos' => $datos['pagination']['total_pedidos'] ?? 0,
                    'datosBodega' => $datos['datos_bodega'] ?? collect(),
                    'notasBodega' => $datos['notas_bodega'] ?? collect(),
                ]);
            }
            
            // Vista de lista
            return view('bodega.index-list', [
                'pedidosPorPagina' => $datos['pedidos_por_pagina'] ?? [],
                'totalPedidos' => $datos['total_pedidos'] ?? 0,
                'paginaActual' => $datos['pagina_actual'] ?? 1,
                'porPagina' => $datos['por_pagina'] ?? 20,
                'search' => $request->query('search', ''),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en PedidosController@index: ' . $e->getMessage());
            
            return back()->with('error', 'Error al cargar los pedidos: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalles de un pedido específico
     */
    public function show(Request $request, $pedidoId)
    {
        try {
            // Obtener el ReciboPrenda para conseguir el numero_pedido
            $reciboPrenda = ReciboPrenda::findOrFail($pedidoId);
            
            // Marcar pedido como visto usando el numero_pedido
            PedidoProduccion::where('numero_pedido', $reciboPrenda->numero_pedido)
                ->update(['viewed_at' => Carbon::now()]);
            
            $datos = $this->bodegaPedidoService->obtenerDetallePedido($pedidoId);
            
            return view('bodega.show', $datos);
            
        } catch (\Exception $e) {
            \Log::error('Error en PedidosController@show: ' . $e->getMessage());
            
            return back()->with('error', 'Error al cargar los detalles del pedido: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar pedidos pendientes de Costura usando CQRS
     */
    public function pendienteCostura(Request $request)
    {
        try {
            // Crear Query con CQRS
            $area = AreaBodega::costura();
            $filtros = [
                'cliente' => $request->query('cliente'),
                'asesor' => $request->query('asesor'),
                'numero_pedido' => $request->query('numero_pedido'),
                'solo_retrasados' => $request->boolean('retrasados', false)
            ];

            $query = new ObtenerPedidosPorAreaQuery(
                $area,
                $filtros,
                $request->get('page', 1),
                15
            );

            // Ejecutar Query usando CQRS Manager
            $resultado = $this->cqrsManager->ask($query);

            if (!$resultado['success']) {
                return back()->with('error', $resultado['message']);
            }

            return view('bodega.index-list', [
                'pedidosPorPagina' => $resultado['pedidos'],
                'totalPedidos' => $resultado['paginacion']['total'],
                'paginaActual' => $resultado['paginacion']['pagina_actual'],
                'porPagina' => $resultado['paginacion']['por_pagina'],
                'search' => $request->query('search', ''),
                'estadisticas' => $resultado['estadisticas'],
                'area' => $resultado['area'],
                'filtros_aplicados' => $resultado['filtros_aplicados'],
                'paginacion_info' => $resultado['paginacion']
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en pendienteCostura (CQRS): ' . $e->getMessage());
            return back()->with('error', 'Error al cargar los pedidos de costura');
        }
    }

    /**
     * Mostrar pedidos pendientes de EPP usando CQRS
     */
    public function pendienteEpp(Request $request)
    {
        try {
            // Crear Query con CQRS
            $area = AreaBodega::epp();
            $filtros = [
                'cliente' => $request->query('cliente'),
                'asesor' => $request->query('asesor'),
                'numero_pedido' => $request->query('numero_pedido'),
                'solo_retrasados' => $request->boolean('retrasados', false)
            ];

            $query = new ObtenerPedidosPorAreaQuery(
                $area,
                $filtros,
                $request->get('page', 1),
                15
            );

            // Ejecutar Query usando CQRS Manager
            $resultado = $this->cqrsManager->ask($query);

            if (!$resultado['success']) {
                return back()->with('error', $resultado['message']);
            }

            return view('bodega.index-list', [
                'pedidosPorPagina' => $resultado['pedidos'],
                'totalPedidos' => $resultado['paginacion']['total'],
                'paginaActual' => $resultado['paginacion']['pagina_actual'],
                'porPagina' => $resultado['paginacion']['por_pagina'],
                'search' => $request->query('search', ''),
                'estadisticas' => $resultado['estadisticas'],
                'area' => $resultado['area'],
                'filtros_aplicados' => $resultado['filtros_aplicados'],
                'paginacion_info' => $resultado['paginacion']
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en pendienteEpp (CQRS): ' . $e->getMessage());
            return back()->with('error', 'Error al cargar los pedidos de EPP');
        }
    }

    /**
     * Marcar pedido como entregado usando CQRS
     */
    public function entregar(Request $request, $id): JsonResponse
    {
        try {
            // Crear Command con CQRS
            $command = new EntregarPedidoCommand(
                $id,
                $request->input('observaciones'),
                auth()->id()
            );

            // Ejecutar Command usando CQRS Manager
            $resultado = $this->cqrsManager->execute($command);

            return response()->json($resultado);

        } catch (\Exception $e) {
            \Log::error('Error en entregar (CQRS): ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como entregado: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener estadísticas de pedidos usando CQRS
     */
    public function estadisticas(Request $request): JsonResponse
    {
        try {
            // Crear Query con CQRS
            $query = new ObtenerEstadisticasPedidosQuery(
                $request->input('areas'), // ['Costura', 'EPP', etc.]
                $request->input('estados'), // ['ENTREGADO', 'EN EJECUCIÓN', etc.]
                $request->input('fecha_desde') ? new \DateTime($request->input('fecha_desde')) : null,
                $request->input('fecha_hasta') ? new \DateTime($request->input('fecha_hasta')) : null
            );

            // Ejecutar Query usando CQRS Manager
            $resultado = $this->cqrsManager->ask($query);

            return response()->json($resultado);

        } catch (\Exception $e) {
            \Log::error('Error en estadisticas (CQRS): ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar estado de pedido usando CQRS
     */
    public function actualizarEstado(Request $request, $id): JsonResponse
    {
        try {
            // Validar y crear Command con CQRS
            $validated = $request->validate([
                'estado' => 'required|string',
                'motivo' => 'nullable|string|max:500',
            ]);

            $nuevoEstado = EstadoPedido::desdeString($validated['estado']);
            
            $command = new ActualizarEstadoPedidoCommand(
                $id,
                $nuevoEstado,
                $validated['motivo'] ?? null,
                auth()->id()
            );

            // Ejecutar Command usando CQRS Manager
            $resultado = $this->cqrsManager->execute($command);

            return response()->json($resultado);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en actualizarEstado (CQRS): ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar cache de queries CQRS
     */
    public function limpiarCache(Request $request): JsonResponse
    {
        try {
            $queryId = $request->input('query_id');
            
            if ($queryId) {
                $this->cqrsManager->clearQueryCacheFor($queryId);
                $message = "Cache limpiado para query: {$queryId}";
            } else {
                $this->cqrsManager->clearQueryCache();
                $message = "Todo el cache de queries ha sido limpiado";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'limpiado_en' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en limpiarCache: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas del sistema CQRS
     */
    public function cqrsStats(): JsonResponse
    {
        try {
            $stats = $this->cqrsManager->getStats();
            
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'generado_en' => now()->toDateTimeString(),
                'version' => '1.0.0'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en cqrsStats: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas CQRS: ' . $e->getMessage()
            ], 500);
        }
    }
    public function actualizarObservaciones(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:recibo_prendas,id',
                'observaciones' => 'nullable|string|max:500',
            ]);

            $reciboPrenda = ReciboPrenda::findOrFail($validated['id']);

            // Validar permiso
            $this->authorize('bodegueroDashboard');

            // Actualizar observaciones
            $reciboPrenda->update([
                'observaciones' => $validated['observaciones'],
            ]);

            // Registrar en auditoría

            return response()->json([
                'success' => true,
                'message' => 'Observaciones actualizadas correctamente',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar observaciones: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Actualizar fecha de entrega
     */
    public function actualizarFecha(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:recibo_prendas,id',
                'fecha_entrega' => 'required|date',
            ]);

            $reciboPrenda = ReciboPrenda::findOrFail($validated['id']);

            // Validar permiso
            $this->authorize('bodegueroDashboard');

            // Actualizar fecha
            $reciboPrenda->update([
                'fecha_entrega' => Carbon::createFromFormat('Y-m-d', $validated['fecha_entrega']),
            ]);

            // Actualizar fecha
            $reciboPrenda->update(['fecha_entrega' => $validated['fecha_entrega']]);

            return response()->json([
                'success' => true,
                'message' => 'Fecha de entrega actualizada correctamente',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar fecha: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener datos de factura para modal - Usa la misma vista que Despacho
     */
    public function obtenerDatosFacturaJSON($id)
    {
        try {
            $resultado = $this->bodegaPedidoService->obtenerDatosFactura($id);
            
            return response()->json($resultado);
            
        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosFacturaJSON: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos'
            ], 500);
        }
    }

    /**
     * Obtener datos de factura
     */
    public function obtenerDatosFactura($id)
    {
        try {
            $resultado = $this->bodegaPedidoService->obtenerDatosFactura($id);
            
            return response()->json($resultado);
            
        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosFactura: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Determinar estado del pedido
     */
    private function determinarEstado($item)
    {
        // Por defecto, retornar null (sin estado asignado)
        return null;
    }

    /**
     * Exportar datos (opcional)
     */
    public function export(Request $request)
    {
        // Implementar exportación a Excel/PDF si es necesario
    }

    /**
     * Dashboard con estadísticas (opcional)
     */
    public function dashboard()
    {
        $totalPedidos = ReciboPrenda::whereDate('created_at', Carbon::today())->count();
        $entregadosHoy = ReciboPrenda::where('estado', 'entregado')
            ->whereDate('fecha_entrega_real', Carbon::today())
            ->count();
        $retrasados = ReciboPrenda::where('estado', '!=', 'entregado')
            ->where('fecha_entrega', '<', Carbon::now())
            ->count();

        return view('bodega.dashboard', [
            'totalPedidos' => $totalPedidos,
            'entregadosHoy' => $entregadosHoy,
            'retrasados' => $retrasados,
        ]);
    }

    /**
     * Guardar detalles de bodega por talla
     */
    public function guardarDetallesTalla(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'talla' => 'required|string',
                'prenda_nombre' => 'nullable|string',
                'asesor' => 'nullable|string',
                'empresa' => 'nullable|string',
                'cantidad' => 'nullable|integer',
                'pendientes' => 'nullable|string',
                'observaciones_bodega' => 'nullable|string',
                'fecha_entrega' => 'nullable|date',
                'fecha_pedido' => 'nullable|date',
                'estado_bodega' => 'nullable|string|in:Pendiente,Entregado',
                'area' => 'nullable|string|in:Costura,EPP,Otro',
                'last_updated_at' => 'nullable|string',
            ]);

            $resultado = $this->bodegaPedidoService->guardarDetalles($validated);
            
            return response()->json($resultado);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en guardarDetallesTalla: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar todos los detalles de un pedido
     */
    public function guardarPedidoCompleto(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'detalles' => 'required|array',
                'detalles.*.talla' => 'nullable|string',  // nullable para permitir EPPs sin talla
                'detalles.*.asesor' => 'nullable|string',  // Guardar asesor
                'detalles.*.empresa' => 'nullable|string',  // Guardar empresa
                'detalles.*.cantidad' => 'nullable|integer',  // Guardar cantidad
                'detalles.*.prenda_nombre' => 'nullable|string',  // Guardar nombre de la prenda
                'detalles.*.pendientes' => 'nullable|string',
                'detalles.*.observaciones_bodega' => 'nullable|string',
                'detalles.*.fecha_entrega' => 'nullable|date',
                'detalles.*.area' => 'nullable|string|in:Costura,EPP,Otro',
                'detalles.*.estado_bodega' => 'nullable|string|in:Pendiente,Entregado,Anulado',
            ]);

            $usuario = auth()->user();
            $pedido = PedidoProduccion::where('numero_pedido', $validated['numero_pedido'])->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            $guardados = 0;
            $camposAuditar = ['asesor', 'empresa', 'cantidad', 'prenda_nombre', 'pendientes', 'observaciones_bodega', 'fecha_entrega', 'area', 'estado_bodega'];
            
            foreach ($validated['detalles'] as $detalle) {
                // La talla puede ser:
                // - Talla real para prendas (S, M, L, etc)
                // - Hash único para EPPs (md5 de nombre+cantidad)
                $talla = $detalle['talla'];
                $nombrePrenda = $detalle['prenda_nombre'] ?? null;
                $cantidad = $detalle['cantidad'] ?? 0;
                
                // Obtener registro anterior para auditoría
                // Búsqueda única: número_pedido + prenda_nombre + talla + cantidad
                $detalleAnterior = \App\Models\BodegaDetallesTalla::where('pedido_produccion_id', $pedido->id)
                    ->where('numero_pedido', $validated['numero_pedido'])
                    ->where('talla', $talla)
                    ->where('prenda_nombre', $nombrePrenda)
                    ->where('cantidad', $cantidad)
                    ->first();

                // Guardar/actualizar registro
                // La clave única es: numero_pedido + prenda_nombre + talla + cantidad
                $detalleGuardado = \App\Models\BodegaDetallesTalla::updateOrCreate(
                    [
                        'pedido_produccion_id' => $pedido->id,
                        'numero_pedido' => $validated['numero_pedido'],
                        'prenda_nombre' => $nombrePrenda,
                        'talla' => $talla,
                        'cantidad' => $cantidad,
                    ],
                    [
                        'asesor' => $detalle['asesor'] ?? null,  // Guardar asesor
                        'empresa' => $detalle['empresa'] ?? null,  // Guardar empresa
                        'pendientes' => $detalle['pendientes'] ?? null,
                        'observaciones_bodega' => $detalle['observaciones_bodega'] ?? null,
                        'fecha_entrega' => $detalle['fecha_entrega'] ?? null,
                        'area' => $detalle['area'] ?? null,
                        'estado_bodega' => $detalle['estado_bodega'] ?? null,
                        'usuario_bodega_id' => $usuario->id,
                        'usuario_bodega_nombre' => $usuario->name,
                    ]
                );

                // Registrar cambios en auditoría
                foreach ($camposAuditar as $campo) {
                    $valorAnterior = $detalleAnterior ? $detalleAnterior->{$campo} : null;
                    $valorNuevo = $detalle[$campo] ?? null;
                    
                    // Convertir null y strings vacíos a representación consistente
                    $valorAnteriorDisplay = ($valorAnterior === null || $valorAnterior === '') ? '' : $valorAnterior;
                    $valorNuevoDisplay = ($valorNuevo === null || $valorNuevo === '') ? '' : $valorNuevo;
                    
                    // Solo registrar si realmente cambió
                    if ($valorAnteriorDisplay !== $valorNuevoDisplay) {
                        \App\Models\BodegaAuditoria::create([
                            'bodega_detalles_talla_id' => $detalleGuardado->id,
                            'numero_pedido' => $validated['numero_pedido'],
                            'talla' => $talla,  // Usar talla tal como es (hash único para EPPs)
                            'campo_modificado' => $campo,
                            'valor_anterior' => $valorAnteriorDisplay,
                            'valor_nuevo' => $valorNuevoDisplay,
                            'usuario_id' => $usuario->id,
                            'usuario_nombre' => $usuario->name,
                            'ip_address' => $request->ip(),
                            'accion' => $detalleAnterior ? 'update' : 'create',
                            'descripcion' => ucfirst($campo) . ' cambió de "' . ($valorAnteriorDisplay ?: 'vacío') . '" a "' . ($valorNuevoDisplay ?: 'vacío') . '"',
                        ]);
                    }
                }
                
                $guardados++;
            }

            return response()->json([
                'success' => true,
                'message' => "$guardados registro(s) guardado(s) correctamente"
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en guardarPedidoCompleto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar una nota de bodega
     */
    public function guardarNota(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'talla' => 'required|string',
                'contenido' => 'required|string|max:5000',
            ]);

            return $this->notaService->guardarNota($validated, $request);
            
        } catch (\Exception $e) {
            \Log::error('Error en guardarNota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la nota: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de notas para un pedido y talla
     */
    public function obtenerNotas(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'talla' => 'required|string',
            ]);

            return $this->notaService->obtenerNotas($validated);
            
        } catch (\Exception $e) {
            \Log::error('Error en obtenerNotas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notas'
            ], 500);
        }
    }

    /**
     * Actualizar una nota existente
     */
    public function actualizarNota(Request $request, $notaId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'contenido' => 'required|string|max:5000',
            ]);

            return $this->notaService->actualizarNota($notaId, $validated);
            
        } catch (\Exception $e) {
            \Log::error('Error en actualizarNota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la nota'
            ], 500);
        }
    }

    /**
     * Eliminar una nota
     */
    public function eliminarNota(Request $request, $notaId): JsonResponse
    {
        try {
            return $this->notaService->eliminarNota($notaId);
            
        } catch (\Exception $e) {
            \Log::error('Error en eliminarNota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la nota'
            ], 500);
        }
    }

}
