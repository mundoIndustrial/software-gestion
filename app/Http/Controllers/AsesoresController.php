<?php

namespace App\Http\Controllers;

use App\Models\ProductoPedido;
use App\Models\PedidoProduccion;
use App\Models\LogoPedido;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\MaterialesOrdenInsumos;
use App\Enums\EstadoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\AsesoresInventarioTelasController;
use App\Application\Services\Asesores\AsesoresApplicationFacadeService;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;

class AsesoresController extends Controller
{
    public function __construct(
        private AsesoresApplicationFacadeService $asesoresService,
        private PedidoProduccionRepository $pedidoProduccionRepository,
    ) {}
    /**
     * Mostrar el perfil del asesor
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                abort(401, 'Por favor inicia sesión para ver tu perfil.');
            }
            
            return view('asesores.profile', compact('user'));
            
        } catch (\Exception $e) {
            abort(500, 'Error al cargar el perfil: ' . $e->getMessage());
        }
    }
    /**
     * Mostrar el dashboard de asesores
     */
    public function dashboard()
    {
        $stats = $this->asesoresService->dashboard->obtenerEstadisticas();
        return view('asesores.dashboard', compact('stats'));
    }

    /**
     * Obtener datos para gráficas del dashboard
     */
    public function getDashboardData(Request $request)
    {
        $dias = $request->get('tipo', 30);
        $datos = $this->asesoresService->dashboard->obtenerDatosGraficas($dias);
        return response()->json($datos);
    }

    /**
     * Listar pedidos del asesor - DELEGADO A SERVICIO
     */
    public function index(Request $request)
    {
        try {
            $tipo = $request->query('tipo');
            $filtros = [];
            
            if ($request->filled('estado')) {
                $filtros['estado'] = $request->estado;
            }
            
            if ($request->filled('search')) {
                $filtros['search'] = $request->search;
            }

            $pedidos = $this->asesoresService->obtenerPedidos->obtener($tipo, $filtros);
            $estados = $tipo !== 'logo' ? $this->asesoresService->obtenerPedidos->obtenerEstados() : [];

            return view('asesores.pedidos.index', compact('pedidos', 'estados'));

        } catch (\Exception $e) {
            \Log::error('Error al listar pedidos: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al listar pedidos');
        }
    }

    /**
     * Mostrar formulario para crear pedido (versión amigable)
     */
    public function create(Request $request)
    {
        $tipo = $request->query('tipo', 'PB'); // Por defecto Prenda/Logo
        $esEdicion = false;
        $cotizacion = null;
        
        // Verificar si es edición de un borrador existente
        if ($request->has('editar')) {
            $cotizacionId = $request->query('editar');
            $cotizacion = \App\Models\Cotizacion::with([
                'cliente',
                'prendas' => function($query) {
                    $query->with(['fotos', 'telaFotos', 'tallas', 'variantes']);
                },
                'logoCotizacion.fotos',
            ])->findOrFail($cotizacionId);
            
            // Debug: verificar telaFotos
            $prenda0 = $cotizacion->prendas->first();
            \Log::info('DEBUG - Cotización cargada para edición DETALLE', [
                'cotizacion_id' => $cotizacionId,
                'prendas_count' => $cotizacion->prendas->count(),
                'prenda_0_id' => $prenda0 ? $prenda0->id : null,
                'prenda_0_telaFotos_count' => $prenda0 ? $prenda0->telaFotos->count() : 0,
                'prenda_0_fotos_count' => $prenda0 ? $prenda0->fotos->count() : 0,
                'prenda_0_tallas_count' => $prenda0 ? $prenda0->tallas->count() : 0,
            ]);
            
            // Debug: convertir a array y verificar
            $cotizacionArray = $cotizacion->toArray();
            \Log::info('DEBUG - toArray() result', [
                'tiene_prendas' => isset($cotizacionArray['prendas']) ? true : false,
                'prendas_count_en_array' => isset($cotizacionArray['prendas']) ? count($cotizacionArray['prendas']) : 0,
                'prenda_0_keys' => isset($cotizacionArray['prendas'][0]) ? array_keys($cotizacionArray['prendas'][0]) : [],
                'prenda_0_tiene_tela_fotos' => isset($cotizacionArray['prendas'][0]['tela_fotos']) ? true : false,
            ]);
            
            // Verificar que el usuario es propietario del borrador
            if ($cotizacion->asesor_id !== \Auth::id() || !$cotizacion->es_borrador) {
                abort(403, 'No tienes permiso para editar este borrador');
            }
            
            $esEdicion = true;
        }
        
        // Si es tipo Logo (B), redirigir a cotización de bordado
        if ($tipo === 'B') {
            return redirect()->route('asesores.cotizaciones-bordado.create');
        }
        
        // Si es tipo Combinada (PL), redirigir a cotización combinada
        if ($tipo === 'PL') {
            return redirect()->route('asesores.cotizaciones-prenda.create');
        }
        
        // Si es tipo Reflectivo (RF), mostrar vista de reflectivo
        if ($tipo === 'RF') {
            return view('asesores.pedidos.create-reflectivo', compact('tipo', 'esEdicion', 'cotizacion'));
        }
        
        return view('asesores.pedidos.create-friendly', compact('tipo', 'esEdicion', 'cotizacion'));
    }

    /**
     * Guardar nuevo pedido - DELEGADO A SERVICIOS
     */
    public function store(Request $request)
    {
        $productosKey = $request->has('productos') ? 'productos' : 'productos_friendly';
        
        $validated = $request->validate([
            'cliente' => 'required|string|max:255',
            'forma_de_pago' => 'nullable|string|max:69',
            'area' => 'nullable|string',
            $productosKey => 'required|array|min:1',
            $productosKey.'.*.nombre_producto' => 'required|string',
            $productosKey.'.*.descripcion' => 'nullable|string',
            $productosKey.'.*.tella' => 'nullable|string',
            $productosKey.'.*.tipo_manga' => 'nullable|string',
            $productosKey.'.*.color' => 'nullable|string',
            $productosKey.'.*.talla' => 'nullable|string',
            $productosKey.'.*.genero' => 'nullable|string',
            $productosKey.'.*.cantidad' => 'required|integer|min:1',
            $productosKey.'.*.ref_hilo' => 'nullable|string',
            $productosKey.'.*.precio_unitario' => 'nullable|numeric|min:0',
            $productosKey.'.*.telas' => 'nullable|array',
            $productosKey.'.*.telas.*.tela_id' => 'nullable|integer',
            $productosKey.'.*.telas.*.color_id' => 'nullable|integer',
            $productosKey.'.*.telas.*.referencia' => 'nullable|string',
            'logo.descripcion' => 'nullable|string',
            'logo.observaciones_tecnicas' => 'nullable|string',
            'logo.tecnicas' => 'nullable|string',
            'logo.ubicaciones' => 'nullable|string',
            'logo.observaciones_generales' => 'nullable|string',
            'logo.imagenes' => 'nullable|array',
            'logo.imagenes.*' => 'nullable|file|image|max:5242880',
            'tipo_cotizacion' => 'nullable|string',
            'cotizacion_id' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            // Determinar si es Logo o Producción
            $tipoCotizacion = $request->input('tipo_cotizacion');
            $cotizacionId = $request->input('cotizacion_id');
            
            if ($this->asesoresService->guardarPedidoLogo->esLogoPedido($tipoCotizacion, $cotizacionId)) {
                // Procesar imágenes del logo
                $imagenesProcesadas = $this->asesoresService->procesarFotosTelas->procesarImagenesLogo($request);
                
                // Guardar logo
                $logoPedidoId = $this->asesoresService->guardarPedidoLogo->guardar($validated, $imagenesProcesadas);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Pedido de logo guardado correctamente',
                    'logo_pedido_id' => $logoPedidoId,
                    'tipo' => 'logo'
                ]);
            }

            // Guardar pedido de producción
            $productosConFotos = $this->asesoresService->procesarFotosTelas->procesar($request, $validated[$productosKey]);
            $pedido = $this->asesoresService->guardarPedidoProduccion->guardar($validated, $productosConFotos);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido guardado como borrador',
                'borrador_id' => $pedido->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al guardar pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar pedido y asignar ID - DELEGADO A SERVICIO
     */
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'borrador_id' => 'required|integer|exists:pedidos_produccion,id',
            'numero_pedido' => 'required|integer|unique:pedidos_produccion,numero_pedido',
        ]);

        try {
            $pedido = $this->asesoresService->confirmarPedido->confirmar($validated['borrador_id'], $validated['numero_pedido']);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente con ID: ' . $validated['numero_pedido'],
                'pedido' => $validated['numero_pedido']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Mostrar un pedido específico - DELEGADO A SERVICIO
     */
    public function show($id)
    {
        try {
            $pedidoData = $this->asesoresService->obtenerPedidoDetalle->obtenerConPrendas($id);
            return view('asesores.pedidos.plantilla-erp-antigua', compact('pedidoData'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mostrar formulario de edición - DELEGADO A SERVICIO
     */
    public function edit($id)
    {
        try {
            $datos = $this->asesoresService->obtenerPedidoDetalle->obtenerParaEdicion($id);
            return view('asesores.pedidos.edit', $datos);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Actualizar pedido - DELEGADO A SERVICIO
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'cliente' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'novedades' => 'nullable|string',
            'forma_de_pago' => 'nullable|string|max:69',
            'estado' => 'nullable|string',
            'area' => 'nullable|string',
            'prendas' => 'sometimes|array',
            'prendas.*.id' => 'nullable|exists:prendas_pedido,id',
            'prendas.*.nombre_prenda' => 'required_with:prendas|string',
            'prendas.*.talla' => 'nullable|string',
            'prendas.*.cantidad' => 'required_with:prendas|integer|min:1',
            'prendas.*.precio_unitario' => 'nullable|numeric|min:0',
        ]);

        try {
            $pedidoActualizado = $this->asesoresService->actualizarPedido->actualizar($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Pedido actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pedido: ' . $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Eliminar pedido
     */
    /**
     * Eliminar un pedido completamente (incluyendo todas sus relaciones)
     */
    public function destroy($id)
    {
        try {
            $resultado = $this->asesoresService->eliminarPedido->eliminarPedido($id);
            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Obtener siguiente número de pedido - DELEGADO A SERVICIO
     */
    public function getNextPedido()
    {
        try {
            $siguientePedido = $this->asesoresService->obtenerProximoPedido->obtenerProximo();

            return response()->json([
                'siguiente_pedido' => $siguientePedido
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener próximo número',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener notificaciones del asesor
     */
    public function getNotificaciones()
    {
        return response()->json($this->asesoresService->notificaciones->obtenerNotificaciones());
    }

    /**
     * Obtener notificaciones del asesor (alias para compatibilidad)
     */
    public function getNotifications()
    {
        return $this->getNotificaciones();
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead()
    {
        try {
            $this->asesoresService->notificaciones->marcarTodosLeidosPedidos();
            
            return response()->json([
                'success' => true,
                'message' => 'Notificaciones marcadas como leídas'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al marcar notificaciones',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar una notificación específica como leída
     */
    public function markNotificationAsRead($notificationId)
    {
        try {
            $this->asesoresService->notificaciones->marcarNotificacionLeida($notificationId);
            
            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al marcar notificación',
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Actualizar el perfil del asesor
     */
    public function updateProfile(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
                'telefono' => 'nullable|string|max:20',
                'ciudad' => 'nullable|string|max:255',
                'departamento' => 'nullable|string|max:255',
                'bio' => 'nullable|string|max:500',
                'password' => 'nullable|string|min:8|confirmed',
                'avatar' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048'
            ]);

            $archivoAvatar = $request->hasFile('avatar') ? $request->file('avatar') : null;
            $resultado = $this->asesoresService->perfil->actualizarPerfil($validated, $archivoAvatar);

            return response()->json($resultado);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Errores de validación: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar perfil: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular pedido con novedad
     */
    public function anularPedido(Request $request, $id)
    {
        $request->validate([
            'novedad' => 'required|string|min:10|max:500',
        ], [
            'novedad.required' => 'La novedad es obligatoria',
            'novedad.min' => 'La novedad debe tener al menos 10 caracteres',
            'novedad.max' => 'La novedad no puede exceder 500 caracteres',
        ]);

        try {
            $pedido = $this->asesoresService->anularPedido->anular($id, $request->novedad);
            
            return response()->json([
                'success' => true,
                'message' => 'Pedido anulado correctamente',
                'pedido' => $pedido,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Mostrar inventario de telas
     */
    public function inventarioTelas()
    {
        return app(AsesoresInventarioTelasController::class)->index();
    }

    /**
     * Obtener datos de la factura de un pedido - DELEGADO A SERVICIO
     */
    public function obtenerDatosFactura($id)
    {
        try {
            $datos = $this->asesoresService->obtenerDatosFactura->obtener($id);
            return response()->json($datos);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error obteniendo datos de la factura: ' . $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Obtener datos de recibos dinámicos para un pedido - DELEGADO A SERVICIO
     */
    public function obtenerDatosRecibos($id)
    {
        try {
            $datos = $this->asesoresService->obtenerDatosRecibos->obtener($id);
            return response()->json($datos);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error obteniendo datos de los recibos: ' . $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
}