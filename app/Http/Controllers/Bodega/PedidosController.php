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
        private BodegaPedidoService $bodegaPedidoService,
        private BodegaRoleService $roleService,
        private BodegaNotaService $notaService,
        private BodegaAuditoriaService $auditoriaService,
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
     * Mostrar pedidos pendientes de Costura
     */
    public function pendienteCostura(Request $request)
    {
        // Obtener usuario y roles
        $usuario = auth()->user();
        $rolesDelUsuario = $usuario->getRoleNames()->toArray();
        
        // Determinar áreas permitidas - solo Costura
        $areasPermitidas = ['Costura'];
        
        // Estados permitidos
        $estadosPermitidos = ['ENTREGADO', 'EN EJECUCIÓN', 'NO INICIADO', 'ANULADA', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA'];
        
        // Obtener TODOS los pedidos con estados permitidos
        $todosLosPedidos = ReciboPrenda::with(['asesor'])
            ->where(function($q) use ($estadosPermitidos) {
                foreach($estadosPermitidos as $estado) {
                    $q->orWhereRaw('UPPER(TRIM(estado)) = ?', [strtoupper($estado)]);
                }
            })
            ->orderBy('numero_pedido', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Filtrar por áreas permitidas (solo Costura)
        $pedidosFiltradosPorRol = $todosLosPedidos->filter(function($item) use ($areasPermitidas) {
            $bdDetalles = BodegaDetallesTalla::where('numero_pedido', $item->numero_pedido)->get();
            
            if ($bdDetalles->isEmpty()) {
                return false;
            }
            
            foreach ($bdDetalles as $detalle) {
                if (in_array($detalle->area, $areasPermitidas)) {
                    return true;
                }
            }
            
            return false;
        })->values();

        // Obtener números de pedidos ÚNICOS
        $numerosPedidosUnicos = $pedidosFiltradosPorRol->pluck('numero_pedido')->unique()->values();
        $totalPedidos = $numerosPedidosUnicos->count();

        // Paginar
        $paginaActual = $request->get('page', 1);
        $porPagina = 15;
        $offset = ($paginaActual - 1) * $porPagina;

        $pedidosPaginados = $numerosPedidosUnicos->slice($offset, $porPagina);

        // Obtener lista de pedidos para esta página
        $pedidosPorPagina = [];
        foreach ($pedidosPaginados as $numeroPedido) {
            $pedidosDelNumero = $pedidosFiltradosPorRol->filter(fn($p) => $p->numero_pedido === $numeroPedido)->values();
            if ($pedidosDelNumero->isNotEmpty()) {
                $primerPedido = $pedidosDelNumero->first();
                $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
                
                $pedidosPorPagina[] = [
                    'id' => $primerPedido->id,
                    'numero_pedido' => $numeroPedido,
                    'cliente' => $primerPedido->cliente ?? 'N/A',
                    'asesor' => $primerPedido->asesor?->nombre ?? $primerPedido->asesor?->name ?? 'N/A',
                    'estado' => $pedidoProduccion?->estado ?? $primerPedido->estado,
                    'fecha_pedido' => $primerPedido->created_at ?? $primerPedido->fecha_pedido,
                    'cantidad_items' => $pedidosDelNumero->count(),
                ];
            }
        }

        $search = $request->query('search', '');

        return view('bodega.index-list', [
            'pedidosPorPagina' => $pedidosPorPagina,
            'totalPedidos' => $totalPedidos,
            'paginaActual' => $paginaActual,
            'porPagina' => $porPagina,
            'search' => $search,
        ]);
    }

    /**
     * Mostrar pedidos pendientes de EPP
     */
    public function pendienteEpp(Request $request)
    {
        // Obtener usuario y roles
        $usuario = auth()->user();
        $rolesDelUsuario = $usuario->getRoleNames()->toArray();
        
        // Determinar áreas permitidas - solo EPP
        $areasPermitidas = ['EPP'];
        
        // Estados permitidos
        $estadosPermitidos = ['ENTREGADO', 'EN EJECUCIÓN', 'NO INICIADO', 'ANULADA', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA'];
        
        // Obtener TODOS los pedidos con estados permitidos
        $todosLosPedidos = ReciboPrenda::with(['asesor'])
            ->where(function($q) use ($estadosPermitidos) {
                foreach($estadosPermitidos as $estado) {
                    $q->orWhereRaw('UPPER(TRIM(estado)) = ?', [strtoupper($estado)]);
                }
            })
            ->orderBy('numero_pedido', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Filtrar por áreas permitidas (solo EPP)
        $pedidosFiltradosPorRol = $todosLosPedidos->filter(function($item) use ($areasPermitidas) {
            $bdDetalles = BodegaDetallesTalla::where('numero_pedido', $item->numero_pedido)->get();
            
            if ($bdDetalles->isEmpty()) {
                return false;
            }
            
            foreach ($bdDetalles as $detalle) {
                if (in_array($detalle->area, $areasPermitidas)) {
                    return true;
                }
            }
            
            return false;
        })->values();

        // Obtener números de pedidos ÚNICOS
        $numerosPedidosUnicos = $pedidosFiltradosPorRol->pluck('numero_pedido')->unique()->values();
        $totalPedidos = $numerosPedidosUnicos->count();

        // Paginar
        $paginaActual = $request->get('page', 1);
        $porPagina = 15;
        $offset = ($paginaActual - 1) * $porPagina;

        $pedidosPaginados = $numerosPedidosUnicos->slice($offset, $porPagina);

        // Obtener lista de pedidos para esta página
        $pedidosPorPagina = [];
        foreach ($pedidosPaginados as $numeroPedido) {
            $pedidosDelNumero = $pedidosFiltradosPorRol->filter(fn($p) => $p->numero_pedido === $numeroPedido)->values();
            if ($pedidosDelNumero->isNotEmpty()) {
                $primerPedido = $pedidosDelNumero->first();
                $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
                
                $pedidosPorPagina[] = [
                    'id' => $primerPedido->id,
                    'numero_pedido' => $numeroPedido,
                    'cliente' => $primerPedido->cliente ?? 'N/A',
                    'asesor' => $primerPedido->asesor?->nombre ?? $primerPedido->asesor?->name ?? 'N/A',
                    'estado' => $pedidoProduccion?->estado ?? $primerPedido->estado,
                    'fecha_pedido' => $primerPedido->created_at ?? $primerPedido->fecha_pedido,
                    'cantidad_items' => $pedidosDelNumero->count(),
                ];
            }
        }

        $search = $request->query('search', '');

        return view('bodega.index-list', [
            'pedidosPorPagina' => $pedidosPorPagina,
            'totalPedidos' => $totalPedidos,
            'paginaActual' => $paginaActual,
            'porPagina' => $porPagina,
            'search' => $search,
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
