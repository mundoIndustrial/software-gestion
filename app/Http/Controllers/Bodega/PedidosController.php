<?php

namespace App\Http\Controllers\Bodega;

use App\Http\Controllers\Controller;
use App\Models\ReciboPrenda;
use App\Models\PedidoProduccion;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\Despacho\UseCases\ObtenerFilasDespachoUseCase;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PedidosController extends Controller
{
    public function __construct(
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private ObtenerFilasDespachoUseCase $obtenerFilas,
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    /**
     * Mostrar lista de pedidos para bodeguero
     */
    public function index(Request $request)
    {
        // Obtener los pedidos de producción ÚNICOS por número de pedido
        $pedidosQuery = ReciboPrenda::with(['asesor'])
            ->where('estado', '!=', 'Anulada')
            ->orderBy('numero_pedido', 'asc')
            ->orderBy('created_at', 'asc')
            ->distinct()
            ->selectRaw('DISTINCT numero_pedido, id, asesor_id, cliente, estado, fecha_estimada_de_entrega, novedades, created_at');

        // Total de pedidos únicos
        $totalPedidos = $pedidosQuery->count();

        // Paginar - 5 pedidos por página
        $paginaActual = $request->get('page', 1);
        $porPagina = 5;
        $offset = ($paginaActual - 1) * $porPagina;

        // Obtener los pedidos de la página actual
        $pedidosPaginados = $pedidosQuery->skip($offset)->take($porPagina)->get();

        // Obtener TODOS los pedidos (para agrupar correctamente)
        $pedidos = ReciboPrenda::with(['asesor'])
            ->where('estado', '!=', 'Anulada')
            ->orderBy('numero_pedido', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Cargar datos de bodega_detalles_talla por numero_pedido y talla
        $datosBodega = \App\Models\BodegaDetallesTalla::all()
            ->keyBy(function ($item) {
                return $item->numero_pedido . '|' . $item->talla;
            });

        // Agrupar solo los pedidos de la página actual
        $pedidosAgrupados = $pedidos
            ->whereIn('numero_pedido', $pedidosPaginados->pluck('numero_pedido'))
            ->groupBy('numero_pedido')
            ->mapWithKeys(function ($items, $numeroPedido) use ($datosBodega) {
            $itemsConTallas = [];
            
            foreach ($items as $item) {
                try {
                    // Obtener datos COMPLETOS del pedido (con variantes, manga, broche, bolsillos)
                    $datosCompletos = $this->obtenerPedidoUseCase->ejecutar($item->id);
                    
                    // Procesar PRENDAS
                    if (isset($datosCompletos->prendas) && is_array($datosCompletos->prendas)) {
                        foreach ($datosCompletos->prendas as $prendaEnriquecida) {
                            $variantes = $prendaEnriquecida['variantes'] ?? [];
                            
                            if (count($variantes) > 0) {
                                $firstRow = true;
                                foreach ($variantes as $variante) {
                                    // Buscar datos de bodega para este numero_pedido, prenda, talla y cantidad
                                    $nombrePrenda = $prendaEnriquecida['nombre_prenda'] ?? $prendaEnriquecida['nombre'] ?? 'Prenda';
                                    $talla = $variante['talla'] ?? '';
                                    $cantidad = $variante['cantidad'] ?? 0;
                                    
                                    $bodegaData = \App\Models\BodegaDetallesTalla::where('numero_pedido', $item->numero_pedido)
                                        ->where('prenda_nombre', $nombrePrenda)
                                        ->where('talla', $talla)
                                        ->where('cantidad', $cantidad)
                                        ->first();

                                    $row = [
                                        'id' => $item->id,
                                        'tipo' => 'prenda',
                                        'numero_pedido' => $item->numero_pedido,
                                        'asesor' => $item->asesor->nombre ?? $item->asesor->name ?? 'N/A',
                                        'asesor_rowspan' => $firstRow ? count($variantes) : 0,
                                        'empresa' => $item->cliente ?? 'N/A',
                                        'empresa_rowspan' => $firstRow ? count($variantes) : 0,
                                        'descripcion' => $prendaEnriquecida,
                                        'descripcion_rowspan' => $firstRow ? count($variantes) : 0,
                                        'talla' => $variante['talla'] ?? '—',
                                        'cantidad_total' => $variante['cantidad'] ?? 0,
                                        'observaciones' => $bodegaData?->observaciones_bodega ?? null,
                                        'pendientes' => $bodegaData?->pendientes ?? null,
                                        'fecha_entrega' => $bodegaData?->fecha_entrega ? Carbon::parse($bodegaData->fecha_entrega)->format('Y-m-d') : ($item->fecha_estimada_de_entrega ? Carbon::parse($item->fecha_estimada_de_entrega)->format('Y-m-d') : null),
                                        'fecha_pedido' => $item->created_at->format('Y-m-d'),
                                        'estado' => $bodegaData?->estado_bodega ?? null,
                                        'area' => $bodegaData?->area ?? null,
                                        'usuario_bodega_nombre' => $bodegaData?->usuario_bodega_nombre ?? null,
                                        'bodega_id' => $bodegaData?->id ?? null,
                                    ];
                                    $itemsConTallas[] = $row;
                                    $firstRow = false;
                                }
                            }
                        }
                    }
                    
                    // Procesar EPPS
                    if (isset($datosCompletos->epps) && is_array($datosCompletos->epps)) {
                        foreach ($datosCompletos->epps as $eppIndex => $eppEnriquecido) {
                            // Crear identificador único para cada EPP (sin depender de talla)
                            $eppNombre = $eppEnriquecido['nombre'] ?? 'EPP';
                            $eppCantidad = $eppEnriquecido['cantidad'] ?? 0;
                            $eppId = md5($item->numero_pedido . '|' . $eppNombre . '|' . $eppCantidad); // Hash único
                            
                            // Buscar datos de bodega usando el identificador del EPP
                            // Para EPPs: buscar por numero_pedido + prenda_nombre + talla (hash) + cantidad
                            $bodegaData = \App\Models\BodegaDetallesTalla::where('numero_pedido', $item->numero_pedido)
                                ->where('prenda_nombre', $eppNombre)
                                ->where('talla', $eppId)
                                ->where('cantidad', $eppCantidad)
                                ->first();

                            $itemsConTallas[] = [
                                'id' => $item->id,
                                'tipo' => 'epp',
                                'numero_pedido' => $item->numero_pedido,
                                'asesor' => $item->asesor->nombre ?? $item->asesor->name ?? 'N/A',
                                'asesor_rowspan' => 1,
                                'empresa' => $item->cliente ?? 'N/A',
                                'empresa_rowspan' => 1,
                                'descripcion' => $eppEnriquecido,
                                'descripcion_rowspan' => 1,
                                'talla' => $eppId,  // Usar hash único como identificador
                                'cantidad_total' => $eppCantidad,
                                'observaciones' => $bodegaData?->observaciones_bodega ?? null,
                                'pendientes' => $bodegaData?->pendientes ?? null,
                                'fecha_entrega' => $bodegaData?->fecha_entrega ? Carbon::parse($bodegaData->fecha_entrega)->format('Y-m-d') : ($item->fecha_estimada_de_entrega ? Carbon::parse($item->fecha_estimada_de_entrega)->format('Y-m-d') : null),
                                'fecha_pedido' => $item->created_at->format('Y-m-d'),
                                'estado' => $bodegaData?->estado_bodega ?? null,
                                'area' => $bodegaData?->area ?? null,
                                'usuario_bodega_nombre' => $bodegaData?->usuario_bodega_nombre ?? null,
                                'bodega_id' => $bodegaData?->id ?? null,
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Fallback: si ObtenerPedidoUseCase falla, usar datos básicos
                    \Log::warning('[Bodega] Error al obtener datos completos del pedido', [
                        'pedido_id' => $item->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return [$numeroPedido => $itemsConTallas];
        })->toArray();

        // Obtener lista única de asesores para filtro
        $asesores = $pedidos->pluck('asesor.nombre')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Crear objeto de paginación manual
        $paginacion = new \Illuminate\Pagination\LengthAwarePaginator(
            $pedidosPaginados,
            $totalPedidos,
            $porPagina,
            $paginaActual,
            [
                'path' => route('gestion-bodega.pedidos'),
                'query' => $request->query(),
            ]
        );

        return view('bodega.pedidos', [
            'pedidosAgrupados' => $pedidosAgrupados,
            'asesores' => $asesores,
            'paginacion' => $paginacion,
            'totalPedidos' => $totalPedidos,
        ]);
    }

    /**
     * Marcar pedido como entregado
     */
    public function entregar(Request $request, $id): JsonResponse
    {
        try {
            $reciboPrenda = ReciboPrenda::findOrFail($id);
            $numeroPedido = $reciboPrenda->numero_pedido;

            // Actualizar estado
            $reciboPrenda->update([
                'estado' => 'entregado',
                'fecha_entrega_real' => Carbon::now(),
            ]);

            // Verificar si todos los artículos del pedido están entregados
            $allDelivered = ReciboPrenda::where('numero_pedido', $numeroPedido)
                ->where('estado', '!=', 'entregado')
                ->doesntExist();

            $pedidoEstado = $allDelivered ? 'entregado' : 'pendiente';

            return response()->json([
                'success' => true,
                'message' => 'Pedido marcado como entregado correctamente',
                'allDelivered' => $allDelivered,
                'pedidoEstado' => $pedidoEstado,
                'data' => [
                    'id' => $reciboPrenda->id,
                    'estado' => 'entregado',
                    'fecha_entrega_real' => $reciboPrenda->fecha_entrega_real,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como entregado: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Actualizar observaciones
     */
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
            // Si es un ID de ReciboPrenda, obtener el pedido asociado
            $reciboPrenda = ReciboPrenda::find($id);
            
            if (!$reciboPrenda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo de prenda no encontrado con ID: ' . $id
                ], 404);
            }
            
            // Obtener el pedido de producción asociado
            $pedido = PedidoProduccion::where('numero_pedido', $reciboPrenda->numero_pedido)->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido de producción no encontrado para numero_pedido: ' . $reciboPrenda->numero_pedido
                ], 404);
            }
            
            // Usar el repositorio que obtiene los datos completos (igual que despacho)
            $datos = $this->pedidoRepository->obtenerDatosFactura($pedido->id);
            
            return response()->json($datos);
        } catch (\Exception $e) {
            \Log::error('[ERROR] Endpoint: /gestion-bodega/pedidos/{id}/factura-datos | Método: obtenerDatosFacturaJSON | ID: ' . $id . ' | Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 400);
        }
    }

    public function obtenerDatosFactura($id)
    {
        try {
            // Obtener el recibo
            $reciboPrenda = ReciboPrenda::find($id);
            
            if (!$reciboPrenda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo de prenda no encontrado'
                ], 404);
            }
            
            // Obtener el pedido de producción asociado
            $pedido = PedidoProduccion::where('numero_pedido', $reciboPrenda->numero_pedido)->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido de producción no encontrado'
                ], 404);
            }
            
            // Usar el repositorio que obtiene los datos completos (igual que despacho)
            $datos = $this->pedidoRepository->obtenerDatosFactura($pedido->id);
            
            return response()->json($datos);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosFactura: ' . $e->getMessage(), [
                'exception' => $e,
                'id' => $id
            ]);
            
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
                'estado_bodega' => 'nullable|string|in:Pendiente,Entregado,Anulado',
                'area' => 'nullable|string|in:Costura,EPP,Otro',
            ]);

            $usuario = auth()->user();
            
            // Obtener el pedido
            $pedido = PedidoProduccion::where('numero_pedido', $validated['numero_pedido'])->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            // Buscar o crear registro
            $detalle = \App\Models\BodegaDetallesTalla::updateOrCreate(
                [
                    'pedido_produccion_id' => $pedido->id,
                    'numero_pedido' => $validated['numero_pedido'],
                    'talla' => $validated['talla'],
                ],
                [
                    'prenda_nombre' => $validated['prenda_nombre'] ?? null,
                    'asesor' => $validated['asesor'] ?? null,
                    'empresa' => $validated['empresa'] ?? null,
                    'cantidad' => $validated['cantidad'] ?? 0,
                    'pendientes' => $validated['pendientes'] ?? null,
                    'observaciones_bodega' => $validated['observaciones_bodega'] ?? null,
                    'fecha_entrega' => $validated['fecha_entrega'] ?? null,
                    'estado_bodega' => $validated['estado_bodega'] ?? null,
                    'area' => $validated['area'] ?? null,
                    'usuario_bodega_id' => $usuario->id,
                    'usuario_bodega_nombre' => $usuario->name,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Detalle guardado correctamente',
                'data' => $detalle
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en guardarDetallesTalla: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);
            
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


}
