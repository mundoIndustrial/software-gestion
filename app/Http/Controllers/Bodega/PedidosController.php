<?php

namespace App\Http\Controllers\Bodega;

use App\Http\Controllers\Controller;
use App\Models\ReciboPrenda;
use App\Models\PedidoProduccion;
use App\Models\PedidoAuditoria;
use App\Models\BodegaDetalleTalla;
use App\Models\CosturaBodegaDetalle;
use App\Application\Bodega\Services\BodegaPedidoService;
use App\Application\Bodega\Services\BodegaRoleService;
use App\Application\Bodega\Services\BodegaNotaService;
use App\Application\Bodega\Services\BodegaAuditoriaService;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\Despacho\UseCases\ObtenerFilasDespachoUseCase;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Application\Bodega\CQRS\CQRSManager;
use App\Application\Bodega\CQRS\Commands\EntregarPedidoCommand;
use App\Application\Bodega\CQRS\Commands\ActualizarEstadoPedidoCommand;
use App\Application\Bodega\CQRS\Queries\ObtenerPedidosPorAreaQuery;
use App\Application\Bodega\CQRS\Queries\ObtenerEstadisticasPedidosQuery;
use App\Domain\Bodega\ValueObjects\AreaBodega;
use App\Domain\Bodega\ValueObjects\EstadoPedido;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
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
     * Mostrar detalles de un pedido específico (solo EPP Pendiente)
     */
    public function showPendienteEpp(Request $request, $pedidoId)
    {
        try {
            // Obtener el ReciboPrenda para conseguir el numero_pedido
            $reciboPrenda = ReciboPrenda::findOrFail($pedidoId);
            $numeroPedido = $reciboPrenda->numero_pedido;
            
            // Marcar pedido como visto usando el numero_pedido
            PedidoProduccion::where('numero_pedido', $numeroPedido)
                ->update(['viewed_at' => Carbon::now()]);
            
            // Obtener datos completos del pedido usando el mismo servicio que show.blade.php
            $datos = $this->bodegaPedidoService->obtenerDetallePedido($pedidoId);
            
            // Filtrar para mostrar solo artículos de EPP con estado_bodega 'Pendiente'
            if (isset($datos['items']) && is_array($datos['items'])) {
                $datos['items'] = array_filter($datos['items'], function($item) {
                    return ($item['area'] ?? '') === 'EPP' && ($item['estado_bodega'] ?? '') === 'Pendiente';
                });
                
                // Reindexar el array después del filtro
                $datos['items'] = array_values($datos['items']);
                
                // Actualizar contadores si existen
                if (isset($datos['estadisticas'])) {
                    $datos['estadisticas']['total_epp_pendientes'] = count($datos['items']);
                }
            }
            
            // Agregar información del filtro aplicado
            $datos['filtro_aplicado'] = [
                'area' => 'EPP',
                'estado' => 'Pendiente',
                'descripcion' => 'Mostrando solo artículos de EPP con estado Pendiente'
            ];
            
            return view('bodega.pendiente-epp-show', $datos);
            
        } catch (\Exception $e) {
            \Log::error('Error en PedidosController@showPendienteEpp: ' . $e->getMessage());
            
            return back()->with('error', 'Error al cargar los detalles del pedido: ' . $e->getMessage());
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
     * Mostrar pedidos pendientes de Costura
     */
    public function pendienteCostura(Request $request)
    {
        try {
            \Log::info('Iniciando pendienteCostura - Verificando tabla bodega_detalles_talla');
            
            // Verificar si la tabla existe
            if (!\Schema::hasTable('bodega_detalles_talla')) {
                \Log::error('La tabla bodega_detalles_talla no existe en la base de datos');
                return back()->with('error', 'La tabla de detalles de talla no está disponible. Contacte al administrador.');
            }
            
            $query = BodegaDetalleTalla::porArea('Costura')
                ->porEstado('Pendiente')
                ->orderBy('fecha_entrega', 'asc');

            \Log::info('Query construida, total registros: ' . $query->count());

            // Aplicar búsqueda general
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('numero_pedido', 'LIKE', "%{$search}%")
                      ->orWhere('empresa', 'LIKE', "%{$search}%")
                      ->orWhere('asesor', 'LIKE', "%{$search}%")
                      ->orWhere('prenda_nombre', 'LIKE', "%{$search}%")
                      ->orWhere('talla', 'LIKE', "%{$search}%");
                });
            }

            // Aplicar filtros específicos
            $filtrosAplicados = [];
            
            if ($request->filled('numero_pedido')) {
                $numerosPedido = explode(',', $request->get('numero_pedido'));
                $query->whereIn('numero_pedido', $numerosPedido);
                $filtrosAplicados['numero_pedido'] = $numerosPedido;
                \Log::info('Aplicando filtro numero_pedido: ' . json_encode($numerosPedido));
            }
            
            if ($request->filled('cliente')) {
                $clientes = explode(',', $request->get('cliente'));
                $query->whereIn('empresa', $clientes);
                $filtrosAplicados['cliente'] = $clientes;
                \Log::info('Aplicando filtro cliente: ' . json_encode($clientes));
            }
            
            if ($request->filled('asesor')) {
                $asesores = explode(',', $request->get('asesor'));
                $query->whereIn('asesor', $asesores);
                $filtrosAplicados['asesor'] = $asesores;
                \Log::info('Aplicando filtro asesor: ' . json_encode($asesores));
            }
            
            if ($request->filled('estado')) {
                $estados = explode(',', $request->get('estado'));
                $query->whereIn('estado_bodega', $estados);
                $filtrosAplicados['estado'] = $estados;
                \Log::info('Aplicando filtro estado: ' . json_encode($estados));
            }
            
            if ($request->filled('fecha_entrega')) {
                $fechas = explode(',', $request->get('fecha_entrega'));
                \Log::info('Fechas recibidas: ' . json_encode($fechas));
                
                $query->where(function($q) use ($fechas) {
                    foreach ($fechas as $index => $fecha) {
                        // Decodificar URL y limpiar espacios
                        $fechaDecodificada = urldecode(trim($fecha));
                        \Log::info("Procesando fecha {$index}: '{$fechaDecodificada}'");
                        
                        try {
                            // Convertir formato d/m/Y a Y-m-d para la consulta
                            $fechaFormateada = \Carbon\Carbon::createFromFormat('d/m/Y', $fechaDecodificada)->format('Y-m-d');
                            \Log::info("Fecha formateada: '{$fechaFormateada}'");
                            
                            if ($index === 0) {
                                $q->whereDate('fecha_entrega', $fechaFormateada);
                            } else {
                                $q->orWhereDate('fecha_entrega', $fechaFormateada);
                            }
                        } catch (\Exception $e) {
                            \Log::error("Error al procesar fecha '{$fechaDecodificada}': " . $e->getMessage());
                            continue;
                        }
                    }
                });
                
                $filtrosAplicados['fecha_entrega'] = $fechas;
                \Log::info('Aplicando filtro fecha_entrega: ' . json_encode($fechas));
            }

            // Filtrar por retrasados si se solicita
            if ($request->boolean('retrasados', false)) {
                $query->retrasados();
                $filtrosAplicados['retrasados'] = true;
            }

            // Paginación
            $porPagina = 15;
            $paginaActual = $request->get('page', 1);
            $totalPedidos = $query->count();
            
            \Log::info("Total pedidos encontrados: {$totalPedidos}, página: {$paginaActual}");
            
            $pedidosPorPagina = $query->skip(($paginaActual - 1) * $porPagina)
                                        ->take($porPagina)
                                        ->get();

            // Obtener estadísticas
            $estadisticas = BodegaDetalleTalla::obtenerEstadisticasCostura();
            
            \Log::info('Estadísticas obtenidas: ' . json_encode($estadisticas));

            // Preparar datos para la vista (similar formato a los pedidos existentes)
            $pedidosFormateados = $pedidosPorPagina->map(function($detalle) {
                return [
                    'id' => $detalle->id,
                    'numero_pedido' => $detalle->numero_pedido,
                    'cliente' => $detalle->empresa,
                    'asesor' => is_string($detalle->asesor) ? $detalle->asesor : 
                               (is_array($detalle->asesor) && isset($detalle->asesor['name']) ? $detalle->asesor['name'] : 
                               (is_object($detalle->asesor) && isset($detalle->asesor->name) ? $detalle->asesor->name : 'No especificado')),
                    'estado' => $detalle->estado_bodega,
                    'area' => $detalle->area,
                    'prenda' => $detalle->prenda_nombre,
                    'talla' => $detalle->talla,
                    'cantidad' => $detalle->cantidad,
                    'pendientes' => $detalle->pendientes,
                    'observaciones' => $detalle->observaciones_bodega,
                    'fecha_pedido' => $detalle->fecha_pedido,
                    'fecha_entrega' => $detalle->fecha_entrega,
                    'usuario_bodega' => $detalle->usuario_bodega_nombre,
                    'created_at' => $detalle->created_at,
                    'updated_at' => $detalle->updated_at,
                    'tiene_pendientes' => $detalle->pendientes > 0,
                    'esta_retrasado' => $detalle->fecha_entrega && $detalle->fecha_entrega < now(),
                ];
            })->toArray(); // Convertir a array

            \Log::info('Pedidos formateados: ' . count($pedidosFormateados));

            return view('bodega.pendiente-costura', [
                'pedidosPorPagina' => $pedidosFormateados,
                'totalPedidos' => $totalPedidos,
                'paginaActual' => $paginaActual,
                'porPagina' => $porPagina,
                'search' => $request->query('search', ''),
                'estadisticas' => $estadisticas,
                'area' => 'Costura',
                'filtros_aplicados' => array_merge([
                    'search' => $request->query('search', ''),
                    'retrasados' => $request->boolean('retrasados', false),
                ], $filtrosAplicados),
                'paginacion_info' => [
                    'pagina_actual' => $paginaActual,
                    'total_paginas' => ceil($totalPedidos / $porPagina),
                    'total' => $totalPedidos,
                    'por_pagina' => $porPagina,
                    'desde' => ($paginaActual - 1) * $porPagina + 1,
                    'hasta' => min($paginaActual * $porPagina, $totalPedidos),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en pendienteCostura: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Error al cargar los pedidos de costura: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar pedidos pendientes de EPP
     */
    public function pendienteEpp(Request $request)
    {
        try {
            \Log::info('Iniciando pendienteEpp - Verificando tabla bodega_detalles_talla');
            
            // Verificar si la tabla existe
            if (!\Schema::hasTable('bodega_detalles_talla')) {
                \Log::error('La tabla bodega_detalles_talla no existe en la base de datos');
                return back()->with('error', 'La tabla de detalles de talla no está disponible. Contacte al administrador.');
            }
            
            $query = BodegaDetalleTalla::porArea('EPP')
                ->porEstado('Pendiente')
                ->orderBy('fecha_entrega', 'asc');

            \Log::info('Query construida, total registros: ' . $query->count());

            // Aplicar búsqueda general
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('numero_pedido', 'LIKE', "%{$search}%")
                      ->orWhere('empresa', 'LIKE', "%{$search}%")
                      ->orWhere('asesor', 'LIKE', "%{$search}%")
                      ->orWhere('prenda_nombre', 'LIKE', "%{$search}%")
                      ->orWhere('talla', 'LIKE', "%{$search}%");
                });
            }

            // Aplicar filtros específicos
            $filtrosAplicados = [];
            
            if ($request->filled('numero_pedido')) {
                $numerosPedido = explode(',', $request->get('numero_pedido'));
                $query->whereIn('numero_pedido', $numerosPedido);
                $filtrosAplicados['numero_pedido'] = $numerosPedido;
                \Log::info('Aplicando filtro numero_pedido: ' . json_encode($numerosPedido));
            }
            
            if ($request->filled('cliente')) {
                $clientes = explode(',', $request->get('cliente'));
                $query->whereIn('empresa', $clientes);
                $filtrosAplicados['cliente'] = $clientes;
                \Log::info('Aplicando filtro cliente: ' . json_encode($clientes));
            }
            
            if ($request->filled('asesor')) {
                $asesores = explode(',', $request->get('asesor'));
                $query->whereIn('asesor', $asesores);
                $filtrosAplicados['asesor'] = $asesores;
                \Log::info('Aplicando filtro asesor: ' . json_encode($asesores));
            }
            
            if ($request->filled('estado')) {
                $estados = explode(',', $request->get('estado'));
                $query->whereIn('estado_bodega', $estados);
                $filtrosAplicados['estado'] = $estados;
                \Log::info('Aplicando filtro estado: ' . json_encode($estados));
            }
            
            if ($request->filled('fecha_entrega')) {
                $fechas = explode(',', $request->get('fecha_entrega'));
                \Log::info('Fechas recibidas: ' . json_encode($fechas));
                
                $query->where(function($q) use ($fechas) {
                    foreach ($fechas as $index => $fecha) {
                        // Decodificar URL y limpiar espacios
                        $fechaDecodificada = urldecode(trim($fecha));
                        \Log::info("Procesando fecha {$index}: '{$fechaDecodificada}'");
                        
                        try {
                            // Convertir formato d/m/Y a Y-m-d para la consulta
                            $fechaFormateada = \Carbon\Carbon::createFromFormat('d/m/Y', $fechaDecodificada)->format('Y-m-d');
                            \Log::info("Fecha formateada: '{$fechaFormateada}'");
                            
                            if ($index === 0) {
                                $q->whereDate('fecha_entrega', $fechaFormateada);
                            } else {
                                $q->orWhereDate('fecha_entrega', $fechaFormateada);
                            }
                        } catch (\Exception $e) {
                            \Log::error("Error al procesar fecha '{$fechaDecodificada}': " . $e->getMessage());
                            continue;
                        }
                    }
                });
                
                $filtrosAplicados['fecha_entrega'] = $fechas;
                \Log::info('Aplicando filtro fecha_entrega: ' . json_encode($fechas));
            }

            // Filtrar por retrasados si se solicita
            if ($request->boolean('retrasados', false)) {
                $query->retrasados();
                $filtrosAplicados['retrasados'] = true;
            }

            // Paginación
            $porPagina = 15;
            $paginaActual = $request->get('page', 1);
            $totalPedidos = $query->count();
            
            \Log::info("Total pedidos encontrados: {$totalPedidos}, página: {$paginaActual}");
            
            $pedidosPorPagina = $query->skip(($paginaActual - 1) * $porPagina)
                                        ->take($porPagina)
                                        ->get();

            // Obtener estadísticas
            $estadisticas = [
                'total' => BodegaDetalleTalla::porArea('EPP')->count(),
                'pendientes' => BodegaDetalleTalla::porArea('EPP')->porEstado('Pendiente')->count(),
                'entregados' => BodegaDetalleTalla::porArea('EPP')->porEstado('Entregado')->count(),
                'anulados' => BodegaDetalleTalla::porArea('EPP')->porEstado('Anulado')->count(),
                'retrasados' => BodegaDetalleTalla::porArea('EPP')->retrasados()->count(),
            ];
            
            \Log::info('Estadísticas obtenidas: ' . json_encode($estadisticas));

            // Preparar datos para la vista (similar formato a los pedidos existentes)
            $pedidosFormateados = $pedidosPorPagina->map(function($detalle) {
                return [
                    'id' => $detalle->id,
                    'numero_pedido' => $detalle->numero_pedido,
                    'cliente' => $detalle->empresa,
                    'asesor' => is_string($detalle->asesor) ? $detalle->asesor : 
                               (is_array($detalle->asesor) && isset($detalle->asesor['name']) ? $detalle->asesor['name'] : 
                               (is_object($detalle->asesor) && isset($detalle->asesor->name) ? $detalle->asesor->name : 'No especificado')),
                    'estado' => $detalle->estado_bodega,
                    'area' => $detalle->area,
                    'prenda' => $detalle->prenda_nombre,
                    'talla' => $detalle->talla,
                    'cantidad' => $detalle->cantidad,
                    'pendientes' => $detalle->pendientes,
                    'observaciones' => $detalle->observaciones_bodega,
                    'fecha_pedido' => $detalle->fecha_pedido,
                    'fecha_entrega' => $detalle->fecha_entrega,
                    'usuario_bodega' => $detalle->usuario_bodega_nombre,
                    'created_at' => $detalle->created_at,
                    'updated_at' => $detalle->updated_at,
                    'tiene_pendientes' => $detalle->pendientes > 0,
                    'esta_retrasado' => $detalle->fecha_entrega && $detalle->fecha_entrega < now(),
                ];
            })->toArray(); // Convertir a array

            \Log::info('Pedidos formateados: ' . count($pedidosFormateados));

            return view('bodega.pendiente-epp', [
                'pedidosPorPagina' => $pedidosFormateados,
                'totalPedidos' => $totalPedidos,
                'paginaActual' => $paginaActual,
                'porPagina' => $porPagina,
                'search' => $request->query('search', ''),
                'estadisticas' => $estadisticas,
                'area' => 'EPP',
                'filtros_aplicados' => array_merge([
                    'search' => $request->query('search', ''),
                    'retrasados' => $request->boolean('retrasados', false),
                ], $filtrosAplicados),
                'paginacion_info' => [
                    'pagina_actual' => $paginaActual,
                    'total_paginas' => ceil($totalPedidos / $porPagina),
                    'total' => $totalPedidos,
                    'por_pagina' => $porPagina,
                    'desde' => ($paginaActual - 1) * $porPagina + 1,
                    'hasta' => min($paginaActual * $porPagina, $totalPedidos),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en pendienteEpp: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Error al cargar los pedidos de EPP: ' . $e->getMessage());
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

    /**
     * Obtener datos para filtros dinámicos
     */
    public function obtenerDatosFiltro(Request $request, string $tipo): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $search = $request->get('search', '');
            $perPage = 20;

            \Log::info('obtenerDatosFiltro iniciado', [
                'tipo' => $tipo,
                'page' => $page,
                'search' => $search,
                'path' => request()->path(),
                'url' => request()->fullUrl()
            ]);

            // Para el módulo de Costura, usar métodos específicos
            if (str_contains(request()->path(), 'pendiente-costura') || str_contains(request()->header('referer'), 'pendiente-costura')) {
                \Log::info('Usando métodos específicos para Costura');
                // Usar métodos específicos para Costura
                switch($tipo) {
                    case 'numero_pedido':
                        $datos = $this->obtenerNumerosPedidoCostura($search, $page, $perPage);
                        break;
                    case 'cliente':
                        $datos = $this->obtenerClientesCostura($search, $page, $perPage);
                        break;
                    case 'asesor':
                        $datos = $this->obtenerAsesoresCostura($search, $page, $perPage);
                        break;
                    case 'estado':
                        $datos = $this->obtenerEstadosCostura($search, $page, $perPage);
                        break;
                    case 'fecha':
                    case 'fecha_entrega':
                        $datos = $this->obtenerFechasCostura($search, $page, $perPage);
                        break;
                    default:
                        \Log::warning('Tipo de filtro no reconocido: ' . $tipo);
                        $datos = collect();
                        break;
                }
            } elseif (str_contains(request()->path(), 'pendiente-epp') || str_contains(request()->header('referer'), 'pendiente-epp')) {
                \Log::info('Usando métodos específicos para EPP');
                // Usar métodos específicos para EPP
                switch($tipo) {
                    case 'numero_pedido':
                        $datos = $this->obtenerNumerosPedidoEpp($search, $page, $perPage);
                        break;
                    case 'cliente':
                        $datos = $this->obtenerClientesEpp($search, $page, $perPage);
                        break;
                    case 'asesor':
                        $datos = $this->obtenerAsesoresEpp($search, $page, $perPage);
                        break;
                    case 'estado':
                        $datos = $this->obtenerEstadosEpp($search, $page, $perPage);
                        break;
                    case 'fecha':
                    case 'fecha_entrega':
                        $datos = $this->obtenerFechasEpp($search, $page, $perPage);
                        break;
                    default:
                        \Log::warning('Tipo de filtro no reconocido: ' . $tipo);
                        $datos = collect();
                        break;
                }
            } else {
                \Log::info('Usando métodos generales para bodega principal');
                // Usar métodos generales para bodega principal
                switch($tipo) {
                    case 'numero_pedido':
                        $datos = $this->obtenerNumerosPedidoCostura($search, $page, $perPage);
                        break;
                    case 'cliente':
                        $datos = $this->obtenerClientesCostura($search, $page, $perPage);
                        break;
                    case 'asesor':
                        $datos = $this->obtenerAsesoresCostura($search, $page, $perPage);
                        break;
                    case 'estado':
                        $datos = $this->obtenerEstadosCostura($search, $page, $perPage);
                        break;
                    case 'fecha':
                    case 'fecha_entrega':
                        $datos = $this->obtenerFechasCostura($search, $page, $perPage);
                        break;
                    default:
                        \Log::warning('Tipo de filtro no reconocido: ' . $tipo);
                        $datos = collect();
                        break;
                }
            }

            \Log::info('Datos obtenidos', [
                'total' => $datos->count(),
                'tipo' => $tipo
            ]);

            $total = $datos->count();
            $paginated = $datos->forPage($page, $perPage);
            
            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($total / $perPage),
                'total' => $total,
                'per_page' => $perPage,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total)
            ];

            return response()->json([
                'success' => true,
                'datos' => $paginated->values()->all(),
                'paginacion' => $pagination
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosFiltro: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('Request info: ' . json_encode([
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'tipo' => $tipo,
                'headers' => request()->headers->all()
            ]));
            
            return response()->json([
                'success' => false,
                'error' => 'Error al cargar datos del filtro: ' . $e->getMessage()
            ], 500);
        }
    }

    private function obtenerClientesUnicos(string|null $search, int $page, int $perPage): Collection
    {
        // Asegurar que $search sea string, no null
        $search = $search ?? '';
        
        $query = ReciboPrenda::select('cliente')
            ->whereNotNull('cliente')
            ->where('cliente', '!=', '')
            ->distinct()
            ->orderBy('cliente', 'asc');

        if ($search) {
            $query->where('cliente', 'LIKE', '%' . $search . '%');
        }

        return $query->get()->pluck('cliente')->map(function($cliente) {
            return ['valor' => $cliente, 'cantidad' => ReciboPrenda::where('cliente', $cliente)->count()];
        });
    }

    private function obtenerAsesoresUnicos(string|null $search, int $page, int $perPage): Collection
    {
        // Asegurar que $search sea string, no null
        $search = $search ?? '';
        
        $query = ReciboPrenda::with(['asesor'])
            ->whereHas('asesor')
            ->select('asesor_id')
            ->distinct()
            ->get()
            ->map(function($recibo) {
                $asesor = $recibo->asesor;
                return $asesor ? ($asesor->nombre ?? $asesor->name ?? 'Sin nombre') : null;
            })
            ->filter()
            ->unique()
            ->sort();

        if ($search) {
            $query = $query->filter(function($nombre) use ($search) {
                return stripos($nombre, $search) !== false;
            });
        }

        return $query->values()->map(function($nombre) {
            return ['valor' => $nombre, 'cantidad' => ReciboPrenda::whereHas('asesor', function($q) use ($nombre) {
                $q->where('name', $nombre)->orWhere('nombre', $nombre);
            })->count()];
        });
    }

    private function obtenerEstadosUnicos(string|null $search, int $page, int $perPage): Collection
    {
        // Asegurar que $search sea string, no null
        $search = $search ?? '';
        
        $estados = [
            ['valor' => 'ENTREGADO', 'texto' => 'Entregado', 'cantidad' => 0],
            ['valor' => 'EN EJECUCIÓN', 'texto' => 'En Ejecución', 'cantidad' => 0],
            ['valor' => 'PENDIENTE_SUPERVISOR', 'texto' => 'Pendiente Supervisor', 'cantidad' => 0],
            ['valor' => 'PENDIENTE_INSUMOS', 'texto' => 'Pendiente Insumos', 'cantidad' => 0],
            ['valor' => 'NO INICIADO', 'texto' => 'No Iniciado', 'cantidad' => 0],
            ['valor' => 'ANULADA', 'texto' => 'Anulada', 'cantidad' => 0],
            ['valor' => 'DEVUELTO_A_ASESORA', 'texto' => 'Devuelto a Asesora', 'cantidad' => 0],
        ];

        foreach ($estados as &$estado) {
            $estado['cantidad'] = ReciboPrenda::where('estado', $estado['valor'])->count();
        }

        if ($search) {
            $estados = collect($estados)->filter(function($estado) use ($search) {
                return stripos($estado['texto'], $search) !== false || stripos($estado['valor'], $search) !== false;
            });
        }

        return collect($estados);
    }

    private function obtenerFechasUnicas(string|null $search, int $page, int $perPage): Collection
    {
        // Asegurar que $search sea string, no null
        $search = $search ?? '';
        
        $query = ReciboPrenda::selectRaw('DATE(created_at) as fecha')
            ->distinct()
            ->orderBy('fecha', 'desc');

        if ($search && strlen($search) >= 4) {
            $query->where('created_at', 'LIKE', '%' . $search . '%');
        }

        return $query->get()->pluck('fecha')->map(function($fecha) {
            return ['valor' => \Carbon\Carbon::parse($fecha)->format('d/m/Y'), 'cantidad' => ReciboPrenda::whereDate('created_at', $fecha)->count()];
        });
    }

    /**
     * Guardar cambios en costura_bodega_detalles
     */
    private function guardarCosturaBodegaDetalle($numeroPedido, $talla, $datos)
    {
        try {
            $detalle = CosturaBodegaDetalle::where('numero_pedido', $numeroPedido)
                ->where('talla', $talla)
                ->first();
            
            if ($detalle) {
                $detalle->update([
                    'estado_bodega' => $datos['estado_bodega'] ?? 'Pendiente',
                    'estado' => $datos['estado'] ?? 'Pendiente',
                    'pendientes' => $datos['pendientes'] ?? '0',
                    'observaciones_bodega' => $datos['observaciones_bodega'] ?? '',
                    'fecha_pedido' => $datos['fecha_pedido'] ?? null,
                    'fecha_entrega' => $datos['fecha_entrega'] ?? null,
                    'usuario_bodega_id' => auth()->id(),
                    'usuario_bodega_nombre' => auth()->user()->name,
                    'updated_at' => now(),
                ]);
            } else {
                // Si no existe, crear nuevo registro
                CosturaBodegaDetalle::create([
                    'pedido_produccion_id' => $datos['pedido_produccion_id'] ?? null,
                    'recibo_prenda_id' => $datos['recibo_prenda_id'] ?? null,
                    'numero_pedido' => $numeroPedido,
                    'talla' => $talla,
                    'prenda_nombre' => $datos['prenda_nombre'] ?? '',
                    'asesor' => $datos['asesor'] ?? '',
                    'empresa' => $datos['empresa'] ?? '',
                    'cantidad' => $datos['cantidad'] ?? 0,
                    'pendientes' => $datos['pendientes'] ?? '0',
                    'observaciones_bodega' => $datos['observaciones_bodega'] ?? '',
                    'fecha_pedido' => $datos['fecha_pedido'] ?? null,
                    'fecha_entrega' => $datos['fecha_entrega'] ?? null,
                    'estado_bodega' => $datos['estado_bodega'] ?? 'Pendiente',
                    'estado' => $datos['estado'] ?? 'Pendiente',
                    'usuario_bodega_id' => auth()->id(),
                    'usuario_bodega_nombre' => auth()->user()->name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Error al guardar en costura_bodega_detalles: ' . $e->getMessage());
            return false;
        }
    }

    // Métodos específicos para filtros de Costura
    private function obtenerNumerosPedidoCostura(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerNumerosPedidoCostura iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::select('numero_pedido')
                ->where('area', 'Costura')
                ->where('estado_bodega', 'Pendiente')
                ->distinct()
                ->orderBy('numero_pedido', 'asc');

            if ($search) {
                $query->where('numero_pedido', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('numero_pedido')->map(function($numero) {
                return ['valor' => $numero, 'cantidad' => BodegaDetalleTalla::where('numero_pedido', $numero)->where('area', 'Costura')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerNumerosPedidoCostura completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerNumerosPedidoCostura: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    private function obtenerClientesCostura(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerClientesCostura iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            // Primero, verificar si hay datos en la tabla
            $totalRegistros = BodegaDetalleTalla::count();
            \Log::info('Total registros en bodega_detalles_talla: ' . $totalRegistros);
            
            $costuraRegistros = BodegaDetalleTalla::where('area', 'Costura')->count();
            \Log::info('Registros con area=Costura: ' . $costuraRegistros);
            
            $pendientesRegistros = BodegaDetalleTalla::where('estado_bodega', 'Pendiente')->count();
            \Log::info('Registros con estado_bodega=Pendiente: ' . $pendientesRegistros);
            
            $costuraPendientes = BodegaDetalleTalla::where('area', 'Costura')->where('estado_bodega', 'Pendiente')->count();
            \Log::info('Registros con area=Costura y estado_bodega=Pendiente: ' . $costuraPendientes);
            
            // Verificar si hay clientes no nulos
            $clientesNoNulos = BodegaDetalleTalla::where('area', 'Costura')->where('estado_bodega', 'Pendiente')->whereNotNull('empresa')->where('empresa', '!=', '')->count();
            \Log::info('Registros con empresa no nula: ' . $clientesNoNulos);
            
            // Mostrar algunos datos de ejemplo
            $ejemplos = BodegaDetalleTalla::where('area', 'Costura')->where('estado_bodega', 'Pendiente')->limit(3)->get(['numero_pedido', 'empresa', 'asesor', 'area', 'estado_bodega']);
            \Log::info('Ejemplos de registros: ' . json_encode($ejemplos));
            
            $query = BodegaDetalleTalla::select('empresa')
                ->where('area', 'Costura')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('empresa')
                ->where('empresa', '!=', '')
                ->distinct()
                ->orderBy('empresa', 'asc');

            if ($search) {
                $query->where('empresa', 'LIKE', '%' . $search . '%');
            }

            \Log::info('SQL Query: ' . $query->toSql());
            \Log::info('Bindings: ' . json_encode($query->getBindings()));

            $result = $query->get()->pluck('empresa')->map(function($cliente) {
                return ['valor' => $cliente, 'cantidad' => BodegaDetalleTalla::where('empresa', $cliente)->where('area', 'Costura')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerClientesCostura completado', ['count' => $result->count()]);
            \Log::info('Resultado: ' . json_encode($result->toArray()));
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerClientesCostura: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    private function obtenerAsesoresCostura(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerAsesoresCostura iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::select('asesor')
                ->where('area', 'Costura')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('asesor')
                ->where('asesor', '!=', '')
                ->distinct()
                ->orderBy('asesor', 'asc');

            if ($search) {
                $query->where('asesor', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('asesor')->map(function($asesor) {
                return ['valor' => $asesor, 'cantidad' => BodegaDetalleTalla::where('asesor', $asesor)->where('area', 'Costura')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerAsesoresCostura completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerAsesoresCostura: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    private function obtenerEstadosCostura(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerEstadosCostura iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $estados = [
                ['valor' => 'Pendiente', 'cantidad' => BodegaDetalleTalla::where('area', 'Costura')->where('estado_bodega', 'Pendiente')->count()],
                ['valor' => 'Entregado', 'cantidad' => BodegaDetalleTalla::where('area', 'Costura')->where('estado_bodega', 'Entregado')->count()],
            ];

            $result = collect($estados)->filter(function($estado) use ($search) {
                return empty($search) || stripos($estado['valor'], $search) !== false;
            });
            
            \Log::info('obtenerEstadosCostura completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerEstadosCostura: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    private function obtenerFechasCostura(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerFechasCostura iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::selectRaw('DATE(fecha_entrega) as fecha')
                ->where('area', 'Costura')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('fecha_entrega')
                ->distinct()
                ->orderBy('fecha', 'desc');

            if ($search && strlen($search) >= 4) {
                $query->where('fecha_entrega', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('fecha')->map(function($fecha) {
                return ['valor' => \Carbon\Carbon::parse($fecha)->format('d/m/Y'), 'cantidad' => BodegaDetalleTalla::whereDate('fecha_entrega', $fecha)->where('area', 'Costura')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerFechasCostura completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerFechasCostura: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    // Métodos específicos para filtros de EPP
    private function obtenerNumerosPedidoEpp(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerNumerosPedidoEpp iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::select('numero_pedido')
                ->where('area', 'EPP')
                ->where('estado_bodega', 'Pendiente')
                ->distinct()
                ->orderBy('numero_pedido', 'asc');

            if ($search) {
                $query->where('numero_pedido', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('numero_pedido')->map(function($numero) {
                return ['valor' => $numero, 'cantidad' => BodegaDetalleTalla::where('numero_pedido', $numero)->where('area', 'EPP')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerNumerosPedidoEpp completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerNumerosPedidoEpp: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    private function obtenerClientesEpp(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerClientesEpp iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::select('empresa')
                ->where('area', 'EPP')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('empresa')
                ->where('empresa', '!=', '')
                ->distinct()
                ->orderBy('empresa', 'asc');

            if ($search) {
                $query->where('empresa', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('empresa')->map(function($cliente) {
                return ['valor' => $cliente, 'cantidad' => BodegaDetalleTalla::where('empresa', $cliente)->where('area', 'EPP')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerClientesEpp completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerClientesEpp: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    private function obtenerAsesoresEpp(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerAsesoresEpp iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::select('asesor')
                ->where('area', 'EPP')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('asesor')
                ->where('asesor', '!=', '')
                ->distinct()
                ->orderBy('asesor', 'asc');

            if ($search) {
                $query->where('asesor', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('asesor')->map(function($asesor) {
                return ['valor' => $asesor, 'cantidad' => BodegaDetalleTalla::where('asesor', $asesor)->where('area', 'EPP')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerAsesoresEpp completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerAsesoresEpp: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    private function obtenerEstadosEpp(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerEstadosEpp iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $estados = [
                ['valor' => 'Pendiente', 'cantidad' => BodegaDetalleTalla::where('area', 'EPP')->where('estado_bodega', 'Pendiente')->count()],
                ['valor' => 'Entregado', 'cantidad' => BodegaDetalleTalla::where('area', 'EPP')->where('estado_bodega', 'Entregado')->count()],
            ];

            $result = collect($estados)->filter(function($estado) use ($search) {
                return empty($search) || stripos($estado['valor'], $search) !== false;
            });
            
            \Log::info('obtenerEstadosEpp completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerEstadosEpp: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    private function obtenerFechasEpp(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerFechasEpp iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::selectRaw('DATE(fecha_entrega) as fecha')
                ->where('area', 'EPP')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('fecha_entrega')
                ->distinct()
                ->orderBy('fecha', 'desc');

            if ($search && strlen($search) >= 4) {
                $query->where('fecha_entrega', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('fecha')->map(function($fecha) {
                return ['valor' => \Carbon\Carbon::parse($fecha)->format('d/m/Y'), 'cantidad' => BodegaDetalleTalla::whereDate('fecha_entrega', $fecha)->where('area', 'EPP')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerFechasEpp completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerFechasEpp: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

}
