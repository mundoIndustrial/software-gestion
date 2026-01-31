<?php

namespace App\Infrastructure\Http\Controllers\Asesores;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AsesoresInventarioTelasController;
use App\Application\Services\Asesores\DashboardService;
use App\Application\Services\Asesores\NotificacionesService;
use App\Application\Services\Asesores\PerfilService;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Models\PedidoProduccion;
use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\AnularProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Application\Pedidos\UseCases\PrepararCreacionProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\AgregarPrendaSimpleUseCase;
use App\Application\Pedidos\UseCases\ObtenerProximoNumeroPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerFacturaUseCase;
use App\Application\Pedidos\UseCases\ObtenerRecibosUseCase;
use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\ConfirmarProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\ActualizarProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\AnularProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Application\Pedidos\DTOs\PrepararCreacionProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\AgregarPrendaSimpleDTO;
use App\Application\Pedidos\DTOs\ObtenerProximoNumeroPedidoDTO;
use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\DTOs\ObtenerRecibosDTO;
use App\Application\Pedidos\UseCases\ObtenerEstadisticasDashboardUseCase;
use App\Application\Pedidos\UseCases\ObtenerDatosGraficasDashboardUseCase;
use App\Application\Pedidos\UseCases\ObtenerNotificacionesUseCase;
use App\Application\Pedidos\UseCases\MarcarNotificacionLeidaUseCase;
use App\Application\Pedidos\UseCases\ObtenerPerfilAsesorUseCase;
use App\Application\Pedidos\UseCases\ActualizarPerfilAsesorUseCase;
use App\Application\Pedidos\DTOs\ObtenerEstadisticasDashboardDTO;
use App\Application\Pedidos\DTOs\ObtenerDatosGraficasDashboardDTO;
use App\Application\Pedidos\DTOs\ObtenerNotificacionesDTO;
use App\Application\Pedidos\DTOs\MarcarNotificacionLeidaDTO;
use App\Application\Pedidos\DTOs\ObtenerPerfilAsesorDTO;
use App\Application\Pedidos\DTOs\ActualizarPerfilAsesorDTO;
use Illuminate\Routing\Controller;

class AsesoresController extends Controller
{
    protected PedidoProduccionRepository $pedidoProduccionRepository;
    protected DashboardService $dashboardService;
    protected NotificacionesService $notificacionesService;
    protected PerfilService $perfilService;
    protected CrearProduccionPedidoUseCase $crearProduccionPedidoUseCase;
    protected ConfirmarProduccionPedidoUseCase $confirmarProduccionPedidoUseCase;
    protected ActualizarProduccionPedidoUseCase $actualizarProduccionPedidoUseCase;
    protected AnularProduccionPedidoUseCase $anularProduccionPedidoUseCase;
    protected ObtenerProduccionPedidoUseCase $obtenerProduccionPedidoUseCase;
    protected ListarProduccionPedidosUseCase $listarProduccionPedidosUseCase;
    protected PrepararCreacionProduccionPedidoUseCase $prepararCreacionProduccionPedidoUseCase;
    protected AgregarPrendaSimpleUseCase $agregarPrendaSimpleUseCase;
    protected ObtenerProximoNumeroPedidoUseCase $obtenerProximoNumeroPedidoUseCase;
    protected ObtenerFacturaUseCase $obtenerFacturaUseCase;
    protected ObtenerRecibosUseCase $obtenerRecibosUseCase;
    protected ObtenerEstadisticasDashboardUseCase $obtenerEstadisticasDashboardUseCase;
    protected ObtenerDatosGraficasDashboardUseCase $obtenerDatosGraficasDashboardUseCase;
    protected ObtenerNotificacionesUseCase $obtenerNotificacionesUseCase;
    protected MarcarNotificacionLeidaUseCase $marcarNotificacionLeidaUseCase;
    protected ObtenerPerfilAsesorUseCase $obtenerPerfilAsesorUseCase;
    protected ActualizarPerfilAsesorUseCase $actualizarPerfilAsesorUseCase;

    public function __construct(
        PedidoProduccionRepository $pedidoProduccionRepository,
        DashboardService $dashboardService,
        NotificacionesService $notificacionesService,
        PerfilService $perfilService,
        CrearProduccionPedidoUseCase $crearProduccionPedidoUseCase,
        ConfirmarProduccionPedidoUseCase $confirmarProduccionPedidoUseCase,
        ActualizarProduccionPedidoUseCase $actualizarProduccionPedidoUseCase,
        AnularProduccionPedidoUseCase $anularProduccionPedidoUseCase,
        ObtenerProduccionPedidoUseCase $obtenerProduccionPedidoUseCase,
        ListarProduccionPedidosUseCase $listarProduccionPedidosUseCase,
        PrepararCreacionProduccionPedidoUseCase $prepararCreacionProduccionPedidoUseCase,
        AgregarPrendaSimpleUseCase $agregarPrendaSimpleUseCase,
        ObtenerProximoNumeroPedidoUseCase $obtenerProximoNumeroPedidoUseCase,
        ObtenerFacturaUseCase $obtenerFacturaUseCase,
        ObtenerRecibosUseCase $obtenerRecibosUseCase,
        ObtenerEstadisticasDashboardUseCase $obtenerEstadisticasDashboardUseCase,
        ObtenerDatosGraficasDashboardUseCase $obtenerDatosGraficasDashboardUseCase,
        ObtenerNotificacionesUseCase $obtenerNotificacionesUseCase,
        MarcarNotificacionLeidaUseCase $marcarNotificacionLeidaUseCase,
        ObtenerPerfilAsesorUseCase $obtenerPerfilAsesorUseCase,
        ActualizarPerfilAsesorUseCase $actualizarPerfilAsesorUseCase
    ) {
        $this->pedidoProduccionRepository = $pedidoProduccionRepository;
        $this->dashboardService = $dashboardService;
        $this->notificacionesService = $notificacionesService;
        $this->perfilService = $perfilService;
        $this->crearProduccionPedidoUseCase = $crearProduccionPedidoUseCase;
        $this->confirmarProduccionPedidoUseCase = $confirmarProduccionPedidoUseCase;
        $this->actualizarProduccionPedidoUseCase = $actualizarProduccionPedidoUseCase;
        $this->anularProduccionPedidoUseCase = $anularProduccionPedidoUseCase;
        $this->obtenerProduccionPedidoUseCase = $obtenerProduccionPedidoUseCase;
        $this->listarProduccionPedidosUseCase = $listarProduccionPedidosUseCase;
        $this->prepararCreacionProduccionPedidoUseCase = $prepararCreacionProduccionPedidoUseCase;
        $this->agregarPrendaSimpleUseCase = $agregarPrendaSimpleUseCase;
        $this->obtenerProximoNumeroPedidoUseCase = $obtenerProximoNumeroPedidoUseCase;
        $this->obtenerFacturaUseCase = $obtenerFacturaUseCase;
        $this->obtenerRecibosUseCase = $obtenerRecibosUseCase;
        $this->obtenerEstadisticasDashboardUseCase = $obtenerEstadisticasDashboardUseCase;
        $this->obtenerDatosGraficasDashboardUseCase = $obtenerDatosGraficasDashboardUseCase;
        $this->obtenerNotificacionesUseCase = $obtenerNotificacionesUseCase;
        $this->marcarNotificacionLeidaUseCase = $marcarNotificacionLeidaUseCase;
        $this->obtenerPerfilAsesorUseCase = $obtenerPerfilAsesorUseCase;
        $this->actualizarPerfilAsesorUseCase = $actualizarPerfilAsesorUseCase;
    }

    /**
     * Mostrar el perfil del asesor
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        try {
            // Crear DTO para el Use Case
            $dto = ObtenerPerfilAsesorDTO::crear();

            // Usar el nuevo Use Case DDD
            $user = $this->obtenerPerfilAsesorUseCase->ejecutar($dto);

            return view('asesores.profile', compact('user'));

        } catch (\Exception $e) {
            abort(500, 'Error al cargar el perfil: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar el dashboard de asesores - DELEGADO A USE CASE
     */
    public function dashboard()
    {
        $dto = ObtenerEstadisticasDashboardDTO::crear();
        $stats = $this->obtenerEstadisticasDashboardUseCase->ejecutar($dto);
        return view('asesores.dashboard', compact('stats'));
    }

    /**
     * Obtener datos para gráficas del dashboard - DELEGADO A USE CASE
     */
    public function getDashboardData(Request $request)
    {
        $dias = (int) $request->get('tipo', 30);
        $dto = ObtenerDatosGraficasDashboardDTO::fromRequest($dias);
        $datos = $this->obtenerDatosGraficasDashboardUseCase->ejecutar($dto);
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

            // Crear DTO para el Use Case
            $dto = ListarProduccionPedidosDTO::fromRequest($tipo, $filtros);

            // Usar el nuevo Use Case DDD
            $pedidos = $this->listarProduccionPedidosUseCase->ejecutar($dto);
            $estados = $tipo !== 'logo' ? $this->listarProduccionPedidosUseCase->obtenerEstados() : [];

            return view('asesores.pedidos.index', compact('pedidos', 'estados'));

        } catch (\Exception $e) {
            \Log::error('Error al listar pedidos: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al listar pedidos');
        }
    }

    /**
     * Mostrar formulario para crear pedido (versión amigable) - DELEGADO A USE CASE
     */
    public function create(Request $request)
    {
        try {
            $editarId = $request->query('editar');
            
            // Crear DTO para el Use Case
            $dto = PrepararCreacionProduccionPedidoDTO::fromRequest(
                tipo: $request->query('tipo', 'PB'),
                editarId: $editarId,
                usuarioId: \Auth::id()
            );

            // Usar el nuevo Use Case DDD
            $datos = $this->prepararCreacionProduccionPedidoUseCase->ejecutar($dto);
            
            $tipo = $datos['tipo'];
            $esEdicion = $datos['esEdicion'];
            $cotizacion = $datos['cotizacion'];
            
            // Redirigir según el tipo
            if ($tipo === 'B') {
                return redirect()->route('asesores.cotizaciones-bordado.create');
            }
            
            if ($tipo === 'PL') {
                return redirect()->route('asesores.cotizaciones-prenda.create');
            }
            
            if ($tipo === 'RF') {
                return view('asesores.pedidos.create-reflectivo', compact('tipo', 'esEdicion', 'cotizacion'));
            }
            
            return view('asesores.pedidos.create-friendly', compact('tipo', 'esEdicion', 'cotizacion'));

        } catch (\Exception $e) {
            \Log::error('Error al preparar formulario de creación: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
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
            $tipoCotizacion = $request->input('tipo_cotizacion');
            $cotizacionId = $request->input('cotizacion_id');
            
            if ($this->guardarPedidoLogoService->esLogoPedido($tipoCotizacion, $cotizacionId)) {
                $imagenesProcesadas = $this->procesarFotosTelasService->procesarImagenesLogo($request);
                $logoPedidoId = $this->guardarPedidoLogoService->guardar($validated, $imagenesProcesadas);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Pedido de logo guardado correctamente',
                    'logo_pedido_id' => $logoPedidoId,
                    'tipo' => 'logo'
                ]);
            }

            $productosConFotos = $this->procesarFotosTelasService->procesar($request, $validated[$productosKey]);
            
            // Crear DTO para el Use Case
            $dto = new CrearProduccionPedidoDTO(
                $validated['cliente'],
                $validated['cliente'],
                $productosConFotos
            );
            
            // Usar el nuevo Use Case DDD
            $pedido = $this->crearProduccionPedidoUseCase->ejecutar($dto);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido guardado como borrador',
                'borrador_id' => $pedido->getId()
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
     * Confirmar pedido y asignar ID - DELEGADO A USE CASE
     */
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'borrador_id' => 'required|integer|exists:pedidos_produccion,id',
            'numero_pedido' => 'required|integer|unique:pedidos_produccion,numero_pedido',
        ]);

        try {
            // Crear DTO para el Use Case
            $dto = new ConfirmarProduccionPedidoDTO(
                (string)$validated['borrador_id']
            );

            // Usar el nuevo Use Case DDD
            $pedido = $this->confirmarProduccionPedidoUseCase->ejecutar($dto);

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
            // Crear DTO para el Use Case
            $dto = ObtenerProduccionPedidoDTO::fromRequest((string)$id);

            // Usar el nuevo Use Case DDD
            $pedidoData = $this->obtenerProduccionPedidoUseCase->ejecutar($dto);

            // DEBUG: Verificar telas_array en show()
            \Log::warning('╔═══ [SHOW - ANTES DE VISTA] ═══╗', [
                'pedidoData_keys' => is_array($pedidoData) ? array_keys($pedidoData) : 'not_array',
                'tiene_pedido_key' => isset($pedidoData['pedido']),
            ]);
            
            $datosEnriquecidos = isset($pedidoData['pedido']) ? $pedidoData['pedido'] : $pedidoData;
            
            if (isset($datosEnriquecidos['prendas']) && count($datosEnriquecidos['prendas']) > 0) {
                $prenda0 = $datosEnriquecidos['prendas'][0];
                \Log::warning('╚═══ SHOW - PRENDA 0 FINAL ═══╝', [
                    'prenda_keys' => array_keys($prenda0),
                    'tiene_telas_array' => isset($prenda0['telas_array']),
                    'cantidad_telas_array' => isset($prenda0['telas_array']) ? count($prenda0['telas_array']) : 0,
                    'telas_array' => $prenda0['telas_array'] ?? 'NULL'
                ]);
            }

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
            // Crear DTO para el Use Case
            $dto = ObtenerProduccionPedidoDTO::fromRequest((string)$id);

            // Usar el nuevo Use Case DDD
            $respuesta = $this->obtenerProduccionPedidoUseCase->ejecutar($dto);
            
            // Extraer el modelo del pedido envuelto por UseCase
            $datosEnriquecidos = isset($respuesta['pedido']) ? $respuesta['pedido'] : $respuesta;
            
            // Para obtener el modelo Eloquent real
            $pedidoModel = \App\Models\PedidoProduccion::find($id);
            
            // DEBUG: Verificar que telas_array está en los datos JUSTO ANTES de enviar a vista
            \Log::warning('╔═══ [ANTES DE ENVIAR A VISTA] ═══╗', [
                'respuesta_keys' => array_keys($respuesta),
                'tiene_pedido_key' => isset($respuesta['pedido']),
                'prendas_count' => isset($datosEnriquecidos['prendas']) ? count($datosEnriquecidos['prendas']) : 0
            ]);
            
            if (isset($datosEnriquecidos['prendas']) && count($datosEnriquecidos['prendas']) > 0) {
                $prenda0 = $datosEnriquecidos['prendas'][0];
                \Log::warning('╚═══ PRENDA 0 FINAL ═══╝', [
                    'prenda_keys' => array_keys($prenda0),
                    'tiene_telas_array' => isset($prenda0['telas_array']),
                    'cantidad_telas_array' => isset($prenda0['telas_array']) ? count($prenda0['telas_array']) : 0,
                    'telas_array' => $prenda0['telas_array'] ?? 'NULL'
                ]);
            }

            return view('asesores.pedidos.editar-pedido', [
                'pedido' => $pedidoModel,
                'pedidoData' => $respuesta,  // Envía la estructura completa con 'pedido' adentro
            ]);
        } catch (\Exception $e) {
            \Log::error('[AsesoresController.edit] Error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Actualizar pedido - DELEGADO A USE CASE
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'cliente' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'novedades' => 'nullable|string',
            'forma_de_pago' => 'nullable|string|max:69',
            'estado' => 'nullable|string|in:Pendiente,Entregado,En Ejecución,No iniciado,Anulada,PENDIENTE_SUPERVISOR',
            'area' => 'nullable|string|max:255',
            'prendas' => 'sometimes|array',
            'prendas.*.id' => 'nullable|exists:prendas_pedido,id',
            'prendas.*.nombre_prenda' => 'required_with:prendas|string',
            'prendas.*.talla' => 'nullable|string',
            'prendas.*.cantidad' => 'required_with:prendas|integer|min:1',
            'prendas.*.precio_unitario' => 'nullable|numeric|min:0',
            'epp' => 'sometimes|array',
            'epp.*.id' => 'required_with:epp|integer|exists:pedido_epp,id',
            'epp.*.cantidad' => 'required_with:epp|integer|min:0',
            'epp.*.observaciones' => 'nullable|string',
        ]);

        try {
            // Crear DTO para el Use Case
            $dto = ActualizarProduccionPedidoDTO::fromRequest((string)$id, $validated);

            // Usar el nuevo Use Case DDD
            $pedidoActualizado = $this->actualizarProduccionPedidoUseCase->ejecutar($dto);

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
     * Anular pedido - DELEGADO A USE CASE
     */
    public function destroy(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'razon' => 'required|string|max:500',
            ]);

            // Crear DTO para el Use Case
            $dto = AnularProduccionPedidoDTO::fromRequest((string)$id, $validated);

            // Usar el nuevo Use Case DDD
            $pedidoAnulado = $this->anularProduccionPedidoUseCase->ejecutarConDTO($dto);

            return response()->json([
                'success' => true,
                'message' => 'Pedido anulado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al anular el pedido: ' . $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Obtener siguiente número de pedido - DELEGADO A SERVICIO
     */
    /**
     * Obtener siguiente número de pedido - DELEGADO A USE CASE
     */
    public function getNextPedido()
    {
        try {
            // Crear DTO para el Use Case
            $dto = ObtenerProximoNumeroPedidoDTO::crear();

            // Usar el nuevo Use Case DDD
            $siguientePedido = $this->obtenerProximoNumeroPedidoUseCase->ejecutar($dto);

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
        $dto = ObtenerNotificacionesDTO::crear();
        $notificaciones = $this->obtenerNotificacionesUseCase->ejecutar($dto);
        return response()->json($notificaciones);
    }

    /**
     * Obtener notificaciones del asesor (alias para compatibilidad)
     */
    public function getNotifications()
    {
        return $this->getNotificaciones();
    }

    /**
     * Marcar todas las notificaciones como leídas - DELEGADO A USE CASE
     */
    public function markAllAsRead()
    {
        try {
            $dto = MarcarNotificacionLeidaDTO::marcarTodos();
            $resultado = $this->marcarNotificacionLeidaUseCase->ejecutar($dto);
            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al marcar notificaciones',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar una notificación específica como leída - DELEGADO A USE CASE
     */
    public function markNotificationAsRead($notificationId)
    {
        try {
            $dto = MarcarNotificacionLeidaDTO::fromRequest($notificationId);
            $resultado = $this->marcarNotificacionLeidaUseCase->ejecutar($dto);
            return response()->json($resultado);
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

            // Crear DTO para el Use Case
            $dto = ActualizarPerfilAsesorDTO::fromRequest($validated, $archivoAvatar);

            // Usar el nuevo Use Case DDD
            $resultado = $this->actualizarPerfilAsesorUseCase->ejecutar($dto);

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
    /**
     * Anular pedido con novedad - DELEGADO A USE CASE (refactorizado de anularPedidoService)
     */
    public function anularPedido(Request $request, $id)
    {
        $validated = $request->validate([
            'novedad' => 'required|string|min:10|max:500',
        ], [
            'novedad.required' => 'La novedad es obligatoria',
            'novedad.min' => 'La novedad debe tener al menos 10 caracteres',
            'novedad.max' => 'La novedad no puede exceder 500 caracteres',
        ]);

        try {
            // Buscar el pedido por numero_pedido (ya que el parámetro $id es el número del pedido, no el ID de BD)
            $pedidoModel = \App\Models\PedidoProduccion::where('numero_pedido', (int)$id)
                ->orWhere('id', (int)$id)
                ->first();
            
            if (!$pedidoModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado',
                ], 404);
            }
            
            // Obtener el ID real del pedido
            $pedidoId = $pedidoModel->id;
            
            // Obtener usuario autenticado
            $usuario = auth()->user();
            $nombreUsuario = $usuario ? $usuario->name : 'Sistema';
            
            // Obtener el rol del usuario
            $rolUsuario = 'Sin rol';
            if ($usuario) {
                try {
                    // Obtener los roles del usuario (relación)
                    $rolesUsuario = $usuario->roles();
                    
                    \Log::info('[anularPedido] Roles del usuario:', [
                        'usuario_id' => $usuario->id,
                        'roles_ids' => $usuario->roles_ids ?? [],
                        'roles_count' => $rolesUsuario->count(),
                    ]);
                    
                    if ($rolesUsuario && $rolesUsuario->count() > 0) {
                        $rolUsuario = $rolesUsuario->first()->name ?? 'Sin rol';
                    }
                } catch (\Exception $e) {
                    \Log::warning('[anularPedido] Error obteniendo roles:', [
                        'error' => $e->getMessage(),
                        'usuario_id' => $usuario->id,
                    ]);
                }
            }
            
            \Log::info('[anularPedido] Información del usuario:', [
                'nombre' => $nombreUsuario,
                'rol' => $rolUsuario,
                'numero_pedido' => $id,
                'pedido_id' => $pedidoId,
            ]);
            
            // Crear DTO para el Use Case con información del usuario
            $dto = AnularProduccionPedidoDTO::fromRequest((string)$pedidoId, [
                'razon' => $validated['novedad'],
                'nombreUsuario' => $nombreUsuario,
                'rolUsuario' => $rolUsuario
            ]);

            // Usar el nuevo Use Case DDD
            $pedidoAnulado = $this->anularProduccionPedidoUseCase->ejecutarConDTO($dto);
            
            return response()->json([
                'success' => true,
                'message' => 'Pedido anulado correctamente',
                'pedido' => $pedidoAnulado,
            ]);
        } catch (\Exception $e) {
            \Log::error('[anularPedido] Error al anular pedido:', [
                'numero_pedido' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
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
    /**
     * Obtener datos de la factura de un pedido - DELEGADO A USE CASE
     */
    public function obtenerDatosFactura($id)
    {
        \Log::warning('⚠️⚠️⚠️ [CONTROLLER-FACTURA] ENDPOINT LLAMADO ⚠️⚠️⚠️', ['pedido_id' => $id]);
        
        try {
            // 🔍 LOGS DE DIAGNÓSTICO - AUTENTICACIÓN Y AUTORIZACIÓN
            $usuarioAutenticado = \Auth::user();
            \Log::info('[DIAGNÓSTICO] Verificando autenticación y autorización', [
                'usuario_id' => $usuarioAutenticado ? $usuarioAutenticado->id : 'NO_AUTENTICADO',
                'usuario_nombre' => $usuarioAutenticado ? $usuarioAutenticado->name : 'ANÓNIMO',
                'usuario_email' => $usuarioAutenticado ? $usuarioAutenticado->email : 'N/A',
                'pedido_id' => $id,
                'ruta_accedida' => \Route::getCurrentRoute()->uri ?? 'desconocida',
                'método_http' => \Request::getMethod(),
            ]);
            
            // 🔍 OBTENER ROLES DEL USUARIO
            if ($usuarioAutenticado) {
                $rolesUsuario = $usuarioAutenticado->roles()->pluck('name')->toArray();
                
                // 🔄 EXTENSIÓN: APLICAR JERARQUÍA DE ROLES (herencia)
                $rolesConHerencia = \App\Services\RoleHierarchyService::getEffectiveRoles($rolesUsuario);
                
                \Log::info('[DIAGNÓSTICO] Roles y permisos del usuario', [
                    'usuario_id' => $usuarioAutenticado->id,
                    'roles' => $rolesUsuario,
                    'roles_con_herencia' => $rolesConHerencia,
                    'tiene_supervisor_pedidos' => in_array('supervisor_pedidos', $rolesConHerencia),
                    'tiene_asesor' => in_array('asesor', $rolesConHerencia),
                    'tiene_admin' => in_array('admin', $rolesConHerencia),
                ]);
            }
            
            \Log::info('[CONTROLLER-FACTURA] Obteniendo datos de factura para pedido: ' . $id);
            
            // Crear DTO para el Use Case
            $dto = ObtenerFacturaDTO::fromRequest((string)$id);

            // Usar el nuevo Use Case DDD
            $datos = $this->obtenerFacturaUseCase->ejecutar($dto);
            
            \Log::info('[CONTROLLER-FACTURA] Datos obtenidos correctamente', [
                'pedido_id' => $id,
                'prendas_count' => count($datos['prendas'] ?? []),
                'procesos_total' => collect($datos['prendas'] ?? [])->sum(fn($p) => count($p['procesos'] ?? []))
            ]);
            
            // LOG CRÍTICO ANTES DE ENVIAR JSON
            if (!empty($datos['prendas'])) {
                foreach ($datos['prendas'] as $idx => $prenda) {
                    \Log::warning('[CONTROLLER-FACTURA-TELAS] Verificación ANTES de JSON', [
                        'prenda_idx' => $idx,
                        'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                        'tiene_telas_array' => isset($prenda['telas_array']),
                        'telas_array_count' => count($prenda['telas_array'] ?? []),
                        'telas_array_full' => json_encode($prenda['telas_array'] ?? []),
                    ]);
                }
            }
            
            \Log::info('✅ [CONTROLLER-FACTURA] Datos de factura obtenidos exitosamente');
            
            // 🔍 LOG FINAL: Verificar estructura exacta antes de retornar
            \Log::info('[CONTROLLER-FACTURA-JSON-RESPONSE] Estructura JSON final que se envía', [
                'estructura_keys' => array_keys($datos),
                'tiene_prendas' => isset($datos['prendas']),
                'prendas_count' => count($datos['prendas'] ?? []),
                'prendas_vacio' => empty($datos['prendas']),
                'prendas_tipo' => gettype($datos['prendas'] ?? null),
                'prendas_es_array' => is_array($datos['prendas'] ?? false),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $datos
            ]);
        } catch (\Exception $e) {
            $usuarioAutenticado = \Auth::user();
            \Log::error('❌ [CONTROLLER-FACTURA] ERROR obteniendo datos de factura', [
                'pedido_id' => $id,
                'usuario_id' => $usuarioAutenticado ? $usuarioAutenticado->id : 'N/A',
                'usuario_nombre' => $usuarioAutenticado ? $usuarioAutenticado->name : 'N/A',
                'error_mensaje' => $e->getMessage(),
                'error_código' => $e->getCode(),
                'error_clase' => get_class($e),
                'archivo' => $e->getFile(),
                'línea' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error obteniendo datos de la factura: ' . $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Obtener datos de recibos dinámicos para un pedido - DELEGADO A SERVICIO
     */
    /**
     * Obtener datos de recibos dinámicos para un pedido - DELEGADO A USE CASE
     */
    public function obtenerDatosRecibos($id)
    {
        try {
            // Crear DTO para el Use Case
            $dto = ObtenerRecibosDTO::fromRequest((string)$id);

            // Usar el nuevo Use Case DDD
            $datos = $this->obtenerRecibosUseCase->ejecutar($dto);
            
            return response()->json($datos);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error obteniendo datos de los recibos: ' . $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Agregar prenda simple al pedido (sin requerimientos complejos)
     */
    /**
     * Agregar prenda simple al pedido - DELEGADO A USE CASE
     */
    public function agregarPrendaSimple(Request $request, $pedidoId)
    {
        try {
            $validated = $request->validate([
                'nombre_prenda' => 'required|string|max:255',
                'cantidad' => 'required|integer|min:1',
                'descripcion' => 'nullable|string|max:1000',
            ]);

            // Crear DTO para el Use Case
            $dto = AgregarPrendaSimpleDTO::fromRequest((string)$pedidoId, $validated);

            // Usar el nuevo Use Case DDD
            $resultado = $this->agregarPrendaSimpleUseCase->ejecutar($dto);

            return response()->json($resultado, 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * API para listar pedidos en tiempo real
     */
    public function apiListar(Request $request)
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

            // Crear DTO para el Use Case
            $dto = ListarProduccionPedidosDTO::fromRequest($tipo, $filtros);

            // Usar el nuevo Use Case DDD
            $pedidos = $this->listarProduccionPedidosUseCase->ejecutar($dto);

            // Transformar a array para JSON
            $pedidosArray = $pedidos->getCollection()->map(function ($pedido) {
                return [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'estado' => $pedido->estado,
                    'area' => $pedido->area,
                    'novedades' => $pedido->novedades,
                    'forma_pago' => $pedido->forma_pago,
                    'fecha_creacion' => $pedido->fecha_creacion,
                    'fecha_estimada' => $pedido->fecha_estimada,
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => $pedidosArray
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en apiListar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al listar pedidos'
            ], 500);
        }
    }
}
