<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\Cotizacion;
use App\Models\Talla;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Http\Requests\CrearPedidoCompletoRequest;
use App\Domain\Pedidos\Services\PedidoWebService;
use App\Application\Services\ImageUploadService;
use App\Application\Services\ColorTelaService;
use App\Domain\Pedidos\DTOs\PedidoNormalizadorDTO;
use App\Domain\Pedidos\Services\ResolutorImagenesService;
use App\Domain\Pedidos\Services\MapeoImagenesService;

/**
 * CrearPedidoEditableController
 * 
 * MASTER Controller para CREACIÓN DE PEDIDOS
 * 
 * REFACTORIZADO (26 Enero 2026):
 * - Separación clara de modelos DOM ↔ Backend
 * - Manejo correcto de imágenes usando UIDs
 * - Normalización y mapeo de referencias
 * 
 * Maneja:
 * 1. Mostrar formulario con todos los datos necesarios (GET /crear)
 * 2. Crear pedido desde cotización o sin cotización (POST /crear)
 * 3. Gestión de items en sesión (agregar, eliminar, listar)
 * 4. Validación de estructura antes de guardar
 */
class CrearPedidoEditableController extends Controller
{
    public function __construct(
        private PedidoWebService $pedidoWebService,
        private ImageUploadService $imageUploadService,
        private ColorTelaService $colorTelaService,
        private ResolutorImagenesService $resolutorImagenes,
        private MapeoImagenesService $mapeoImagenes
    ) {}

    /**
     * Crear pedido DESDE COTIZACIÓN
     * GET /asesores/pedidos-editable/crear-desde-cotizacion
     * 
     * Carga todas las cotizaciones para seleccionar una y crear el pedido basado en ella
     * 
     * @param Request $request
     * @return View
     */
    public function crearDesdeCotizacion(Request $request): View
    {
        $inicioTotal = microtime(true);
        Log::info('[CREAR-DESDE-COTIZACION] ⏱️ INICIANDO CARGA DE PÁGINA', [
            'usuario_id' => Auth::id(),
            'timestamp' => now(),
        ]);
        
        $user = Auth::user();
        Log::info('[CREAR-DESDE-COTIZACION] ✅ Usuario obtenido', [
            'usuario_id' => $user->id,
            'usuario_nombre' => $user->name,
        ]);
        
        // ========================================
        // DATOS COMPARTIDOS (SIEMPRE)
        // ========================================
        
        // Obtener las tallas disponibles
        $inicioTallas = microtime(true);
        $tallas = Talla::all();
        $tiempoTallas = round((microtime(true) - $inicioTallas) * 1000, 2);
        Log::info('[CREAR-DESDE-COTIZACION] 📏 Tallas cargadas', [
            'cantidad' => $tallas->count(),
            'tiempo_ms' => $tiempoTallas,
        ]);
        
        // Formas de pago disponibles (ValueObject)
        $formasPago = [
            'Contado',
            'Crédito 15 días',
            'Crédito 30 días',
            'Crédito 60 días',
            'Transferencia',
            'Cheque'
        ];
        Log::debug('[CREAR-DESDE-COTIZACION] 💳 Formas de pago configuradas', ['cantidad' => count($formasPago)]);
        
        // Técnicas disponibles (se definen en JavaScript frontend, pasamos array simple)
        $tecnicas = [
            'Bordado',
            'Estampado',
            'DTF',
            'Sublimado',
            'Tejido',
            'Serigrafía'
        ];
        Log::debug('[CREAR-DESDE-COTIZACION]  Técnicas configuradas', ['cantidad' => count($tecnicas)]);
        
        // ========================================
        // DATO CRÍTICO: COTIZACIONES DEL USUARIO (IMPORTANTE AQUÍ)
        // ========================================
        
        // Cargar cotizaciones aprobadas para crear pedidos
        $inicioCotizaciones = microtime(true);
        $cotizaciones = Cotizacion::with([
            'cliente',
            'tipoCotizacion',  // ✅ Agregar el tipo de cotización
            'prendas' => function($query) {
                $query->with([
                    'fotos', 
                    'telaFotos', 
                    'tallas', 
                    'variantes',
                    'reflectivo.fotos'  // ✅ Agregar fotos de reflectivo para imágenes del proceso
                ]);
            },
            'logoCotizacion.fotos',
            'reflectivoCotizacion.fotos'
        ])
            ->where('asesor_id', $user->id)
            ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
            ->orderBy('created_at', 'desc')
            ->get();
        $tiempoCotizaciones = round((microtime(true) - $inicioCotizaciones) * 1000, 2);
        Log::info('[CREAR-DESDE-COTIZACION] 📋 Cotizaciones cargadas (CON RELACIONES)', [
            'cantidad' => $cotizaciones->count(),
            'tiempo_ms' => $tiempoCotizaciones,
            'nota' => 'Este es el tiempo MÁS CRÍTICO - incluye carga de prendas, fotos, tallas, variantes',
        ]);
        
        // ========================================
        // DATO CRÍTICO: PEDIDOS EXISTENTES
        // ========================================
        
        // Obtener pedidos disponibles para edición
        $inicioPedidos = microtime(true);
        $pedidos = PedidoProduccion::where('asesor_id', $user->id)
            ->where('estado', '!=', 'completado')
            ->orderBy('created_at', 'desc')
            ->get();
        $tiempoPedidos = round((microtime(true) - $inicioPedidos) * 1000, 2);
        Log::info('[CREAR-DESDE-COTIZACION] 📦 Pedidos existentes cargados', [
            'cantidad' => $pedidos->count(),
            'tiempo_ms' => $tiempoPedidos,
            'usuario_id' => $user->id,
        ]);
        
        // ========================================
        // DATO CRÍTICO: CLIENTES
        // ========================================
        
        // Obtener clientes para dropdown manual si se crea sin cotización
        $inicioClientes = microtime(true);
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        $tiempoClientes = round((microtime(true) - $inicioClientes) * 1000, 2);
        Log::info('[CREAR-DESDE-COTIZACION] 👥 Clientes cargados', [
            'cantidad' => $clientes->count(),
            'tiempo_ms' => $tiempoClientes,
        ]);
        
        // ========================================
        // RETORNAR VIEW CON TODOS LOS DATOS
        // ========================================
        
        $inicioView = microtime(true);
        $view = view('asesores.pedidos.crear-pedido-desde-cotizacion', [
            'cotizacionesData' => $cotizaciones,
            'pedidos' => $pedidos,
            'clientes' => $clientes,
            'tallas' => $tallas,
            'tecnicas' => $tecnicas,
            'formasPago' => $formasPago,
            'modoEdicion' => false
        ]);
        $tiempoView = round((microtime(true) - $inicioView) * 1000, 2);
        
        $tiempoTotalMs = round((microtime(true) - $inicioTotal) * 1000, 2);
        Log::info('[CREAR-DESDE-COTIZACION] ✨ PÁGINA COMPLETADA', [
            'tiempo_total_ms' => $tiempoTotalMs,
            'tiempo_tallas_ms' => $tiempoTallas,
            'tiempo_cotizaciones_ms' => $tiempoCotizaciones,
            'tiempo_pedidos_ms' => $tiempoPedidos,
            'tiempo_clientes_ms' => $tiempoClientes,
            'tiempo_view_ms' => $tiempoView,
            'resumen' => "Tallas: {$tiempoTallas}ms | Cotizaciones: {$tiempoCotizaciones}ms | Pedidos: {$tiempoPedidos}ms | Clientes: {$tiempoClientes}ms | View: {$tiempoView}ms | TOTAL: {$tiempoTotalMs}ms",
        ]);
        
        return $view;
    }

    /**
     * Crear PEDIDO NUEVO (sin cotización)
     * GET /asesores/pedidos-editable/crear-nuevo
     * 
     * Muestra formulario vacío para crear pedido manual sin usar cotización
     * 
     * @param Request $request
     * @return View
     */
    public function crearNuevo(Request $request): View
    {
        $inicioTotal = microtime(true);
        Log::info('[CREAR-PEDIDO-NUEVO] ⏱️ INICIANDO CARGA DE PÁGINA', [
            'usuario_id' => Auth::id(),
            'timestamp' => now(),
        ]);
        
        $user = Auth::user();
        Log::info('[CREAR-PEDIDO-NUEVO] ✅ Usuario obtenido', [
            'usuario_id' => $user->id,
            'usuario_nombre' => $user->name,
        ]);
        
        // ========================================
        // DATOS COMPARTIDOS (SIEMPRE)
        // ========================================
        
        // Obtener las tallas disponibles
        $inicioTallas = microtime(true);
        $tallas = Talla::all();
        $tiempoTallas = round((microtime(true) - $inicioTallas) * 1000, 2);
        Log::info('[CREAR-PEDIDO-NUEVO] 📏 Tallas cargadas', [
            'cantidad' => $tallas->count(),
            'tiempo_ms' => $tiempoTallas,
        ]);
        
        // Formas de pago disponibles (ValueObject)
        $formasPago = [
            'Contado',
            'Crédito 15 días',
            'Crédito 30 días',
            'Crédito 60 días',
            'Transferencia',
            'Cheque'
        ];
        Log::debug('[CREAR-PEDIDO-NUEVO] 💳 Formas de pago configuradas', ['cantidad' => count($formasPago)]);
        
        // Técnicas disponibles (se definen en JavaScript frontend, pasamos array simple)
        $tecnicas = [
            'Bordado',
            'Estampado',
            'DTF',
            'Sublimado',
            'Tejido',
            'Serigrafía'
        ];
        Log::debug('[CREAR-PEDIDO-NUEVO]  Técnicas configuradas', ['cantidad' => count($tecnicas)]);
        
        // ========================================
        // COTIZACIONES: Vacía para crear nuevo
        // ========================================
        // NO cargamos cotizaciones, el usuario crea pedido desde cero con cliente manual
        $cotizaciones = collect([]);
        Log::debug('[CREAR-PEDIDO-NUEVO] 📋 Cotizaciones (vacías para modo nuevo)', ['cantidad' => 0]);
        
        // ========================================
        // DATO CRÍTICO: PEDIDOS EXISTENTES
        // ========================================
        
        // Obtener pedidos disponibles para edición
        $inicioPedidos = microtime(true);
        $pedidos = PedidoProduccion::where('asesor_id', $user->id)
            ->where('estado', '!=', 'completado')
            ->orderBy('created_at', 'desc')
            ->get();
        $tiempoPedidos = round((microtime(true) - $inicioPedidos) * 1000, 2);
        Log::info('[CREAR-PEDIDO-NUEVO] 📦 Pedidos existentes cargados', [
            'cantidad' => $pedidos->count(),
            'tiempo_ms' => $tiempoPedidos,
            'usuario_id' => $user->id,
        ]);
        
        // ========================================
        // DATO CRÍTICO: CLIENTES (IMPORTANTE AQUÍ)
        // ========================================
        
        // Obtener todos los clientes para dropdown manual (ESENCIAL para "Pedido Nuevo")
        $inicioClientes = microtime(true);
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        $tiempoClientes = round((microtime(true) - $inicioClientes) * 1000, 2);
        Log::info('[CREAR-PEDIDO-NUEVO] 👥 Clientes cargados', [
            'cantidad' => $clientes->count(),
            'tiempo_ms' => $tiempoClientes,
        ]);
        
        // ========================================
        // RETORNAR VIEW CON TODOS LOS DATOS
        // ========================================
        
        $inicioView = microtime(true);
        $view = view('asesores.pedidos.crear-pedido-nuevo', [
            'cotizaciones' => $cotizaciones,
            'pedidos' => $pedidos,
            'clientes' => $clientes,
            'tallas' => $tallas,
            'tecnicas' => $tecnicas,
            'formasPago' => $formasPago,
            'modoEdicion' => false
        ]);
        $tiempoView = round((microtime(true) - $inicioView) * 1000, 2);
        
        $tiempoTotalMs = round((microtime(true) - $inicioTotal) * 1000, 2);
        Log::info('[CREAR-PEDIDO-NUEVO] ✨ PÁGINA COMPLETADA', [
            'tiempo_total_ms' => $tiempoTotalMs,
            'tiempo_tallas_ms' => $tiempoTallas,
            'tiempo_pedidos_ms' => $tiempoPedidos,
            'tiempo_clientes_ms' => $tiempoClientes,
            'tiempo_view_ms' => $tiempoView,
            'resumen' => "Tallas: {$tiempoTallas}ms | Pedidos: {$tiempoPedidos}ms | Clientes: {$tiempoClientes}ms | View: {$tiempoView}ms | TOTAL: {$tiempoTotalMs}ms",
        ]);
        
        return $view;
    }

    /**
     * Agregar item al carrito de pedido
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function agregarItem(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'prenda_id' => 'nullable|integer',
                'nombre_prenda' => 'required|string|max:255',
                'cantidad' => 'required|integer|min:1',
                'descripcion' => 'nullable|string',
            ]);

            // Aquí iría la lógica para agregar el item
            return response()->json([
                'success' => true,
                'message' => 'Item agregado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar item: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Eliminar item del carrito
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function eliminarItem(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'index' => 'required|integer|min:0',
            ]);

            // Lógica para eliminar item

            return response()->json([
                'success' => true,
                'message' => 'Item eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar item: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtener items del carrito
     * 
     * @return JsonResponse
     */
    public function obtenerItems(): JsonResponse
    {
        try {
            // Obtener items de la sesión o estado global
            $items = session('items_pedido', []);

            return response()->json([
                'success' => true,
                'items' => $items,
                'count' => count($items),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener items: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validar datos del pedido antes de crear
     * 
     *  REFACTORIZADO (26 Enero 2026):
     * - Decodifica JSON del campo "pedido" 
     * - Valida estructura con prendas y/o epps
     * - Permite pedidos con SOLO epps (sin prendas)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validarPedido(Request $request): JsonResponse
    {
        try {
            // PASO 1: Decodificar JSON metadata del campo "pedido"
            $pedidoJSON = $request->input('pedido');
            if (!$pedidoJSON) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo "pedido" JSON requerido',
                    'errors' => ['pedido' => ['Campo "pedido" JSON requerido en FormData']],
                ], 422);
            }

            $validated = json_decode($pedidoJSON, true);
            if (!$validated) {
                return response()->json([
                    'success' => false,
                    'message' => 'JSON inválido en campo "pedido"',
                    'errors' => ['pedido' => ['JSON inválido']],
                ], 422);
            }

            // DEBUG: Log estructura recibida
            Log::info('[CrearPedidoEditableController] validarPedido - Estructura decodificada', [
                'cliente' => $validated['cliente'] ?? 'SIN CLIENTE',
                'tiene_prendas' => isset($validated['prendas']) ? count($validated['prendas']) : 'NO EXISTE',
                'tiene_epps' => isset($validated['epps']) ? count($validated['epps']) : 'NO EXISTE',
                'tiene_items_legacy' => isset($validated['items']) ? count($validated['items']) : 'NO EXISTE',
                'keys_recibidas' => array_keys($validated),
            ]);

            // PASO 2: Validar que exista al menos prendas O epps
            $tienePrendas = !empty($validated['prendas']) && count($validated['prendas']) > 0;
            $tieneEpps = !empty($validated['epps']) && count($validated['epps']) > 0;
            $tieneItemsLegacy = !empty($validated['items']) && count($validated['items']) > 0;

            $errores = [];

            // Validar cliente
            if (empty($validated['cliente'])) {
                $errores['cliente'] = ['El cliente es requerido'];
            }

            // Validar al menos prendas O epps O items
            if (!$tienePrendas && !$tieneEpps && !$tieneItemsLegacy) {
                $errores['items'] = ['Debe agregar al menos una prenda o un EPP'];
            }

            // Si hay errores, retornar
            if (!empty($errores)) {
                Log::warning('[CrearPedidoEditableController] validarPedido - Validación fallida', [
                    'errores' => $errores,
                    'tienePrendas' => $tienePrendas,
                    'tieneEpps' => $tieneEpps,
                    'tieneItems' => $tieneItemsLegacy,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validación fallida',
                    'errors' => $errores,
                ], 422);
            }

            // PASO 3: Obtener o crear cliente
            $clienteNombre = trim($validated['cliente']);
            $cliente = $this->obtenerOCrearCliente($clienteNombre);

            Log::info('[CrearPedidoEditableController] validarPedido - Validación exitosa', [
                'cliente_id' => $cliente->id,
                'prendas' => $tienePrendas ? count($validated['prendas']) : 0,
                'epps' => $tieneEpps ? count($validated['epps']) : 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Validación exitosa',
                'cliente_id' => $cliente->id,
            ]);

        } catch (\Exception $e) {
            Log::error('[CrearPedidoEditableController] validarPedido - Error', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 422);
        }
    }


    /**
     * Obtener cliente existente o crear uno nuevo
     * 
     * @param string $nombre
     * @return Cliente
     */
    private function obtenerOCrearCliente(string $nombre): Cliente
    {
        // Buscar cliente por nombre
        $cliente = Cliente::where('nombre', 'LIKE', $nombre)->first();
        
        if ($cliente) {
            \Log::info('[CrearPedidoEditableController] Cliente existente encontrado', [
                'cliente_id' => $cliente->id,
                'nombre' => $cliente->nombre
            ]);
            return $cliente;
        }
        
        // Crear cliente nuevo si no existe
        $cliente = Cliente::create([
            'nombre' => $nombre,
            'email' => null,
            'telefono' => null,
            'direccion' => null,
            'ciudad' => null,
            'estado' => 'activo',
        ]);
        
        \Log::info('[CrearPedidoEditableController] Cliente nuevo creado', [
            'cliente_id' => $cliente->id,
            'nombre' => $cliente->nombre
        ]);
        
        return $cliente;
    }

    /**
     * CREAR PEDIDO CON IMÁGENES - 100% TRANSACCIONAL (REFACTORIZADO)
     * POST /asesores/pedidos-editable/crear
     * 
     * FLUJO NUEVO (26 Enero 2026):
     * 1. Decodificar JSON del frontend
     * 2. Normalizar usando PedidoNormalizadorDTO (extrae UIDs)
     * 3. Crear pedido base en BD
     * 4. Usar MapeoImagenesService para:
     *    - Resolver imágenes (UIDs → rutas)
     *    - Crear registros de fotos
     * 
     * TODO O NADA: Si falla algo, rollback completo (DB + archivos)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function crearPedido(Request $request): JsonResponse
    {
        $pedidoId = null;
        $inicioTotal = microtime(true);

        try {
            Log::info('[CREAR-PEDIDO] ⏱️ INICIANDO CREACIÓN TRANSACCIONAL', [
                'has_pedido_json' => !!$request->input('pedido'),
                'archivos_count' => count($request->allFiles()),
                'timestamp' => now(),
            ]);
            
            // DEBUG: Mostrar qué archivos se recibieron en FormData
            // DEBUG: Ver TODOS los files recibidos (incluyendo anidados)
            $allInputs = $request->all();
            $archivosRecibidos = [];
            
            // Buscar archivos en TODOS los inputs (esto incluye archivos anidados como prendas[0][imagenes][0])
            $this->buscarArchivosAnidados($allInputs, '', $archivosRecibidos);
            
            Log::debug('[CREAR-PEDIDO] 📤 Archivos en FormData', [
                'total_archivos' => count($archivosRecibidos),
                'archivos' => $archivosRecibidos,
                'nota' => 'Si archivos está vacío aquí, el problema está en el frontend (FormData no se construyó correctamente)'
            ]);

            // ====== PASO 1: Decodificar JSON del frontend ======
            $inicioPaso1 = microtime(true);
            $pedidoJSON = $request->input('pedido');
            if (!$pedidoJSON) {
                throw new \Exception('Campo "pedido" JSON requerido');
            }

            $datosFrontend = json_decode($pedidoJSON, true);
            if (!$datosFrontend) {
                throw new \Exception('JSON inválido en campo "pedido"');
            }
            
            // DEBUG: Validar que no hay File objects en el JSON
            $this->validarJsonSinFiles($datosFrontend);
            $tiempoPaso1 = round((microtime(true) - $inicioPaso1) * 1000, 2);
            Log::info('[CREAR-PEDIDO] ✅ PASO 1: JSON decodificado', ['tiempo_ms' => $tiempoPaso1]);

            // ====== PASO 2: Obtener/crear cliente ======
            $inicioPaso2 = microtime(true);
            $clienteNombre = trim($datosFrontend['cliente'] ?? '');
            $cliente = $this->obtenerOCrearCliente($clienteNombre);
            $tiempoPaso2 = round((microtime(true) - $inicioPaso2) * 1000, 2);

            Log::info('[CREAR-PEDIDO] ✅ PASO 2: Cliente obtenido/creado', [
                'cliente_id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'tiempo_ms' => $tiempoPaso2,
            ]);

            // ====== PASO 3: Normalizar usando DTO ======
            $inicioPaso3 = microtime(true);
            $dtoPedido = PedidoNormalizadorDTO::fromFrontendJSON(
                $datosFrontend,
                $cliente->id
            );
            $tiempoPaso3 = round((microtime(true) - $inicioPaso3) * 1000, 2);

            Log::info('[CREAR-PEDIDO] ✅ PASO 3: Pedido normalizado (DTO)', [
                'cliente_id' => $dtoPedido->cliente_id,
                'prendas' => count($dtoPedido->prendas),
                'epps' => count($dtoPedido->epps),
                'tiempo_ms' => $tiempoPaso3,
            ]);

            // ====== PASO 4: Iniciar transacción ======
            DB::beginTransaction();
            Log::debug('[CREAR-PEDIDO] 🔄 Transacción DB iniciada');

            // ====== PASO 5: Crear pedido base ======
            $inicioPaso5 = microtime(true);
            // Convertir DTO a array para compatibilidad con PedidoWebService
            $datosParaServicio = [
                'cliente' => $dtoPedido->cliente,
                'asesora' => $dtoPedido->asesora,
                'forma_de_pago' => $dtoPedido->forma_de_pago,
                'cliente_id' => $dtoPedido->cliente_id,
                'items' => $dtoPedido->prendas,
                'epps' => $dtoPedido->epps,
            ];

            $pedido = $this->pedidoWebService->crearPedidoCompleto(
                $datosParaServicio,
                Auth::id()
            );

            $pedidoId = $pedido->id;
            $tiempoPaso5 = round((microtime(true) - $inicioPaso5) * 1000, 2);

            Log::info('[CREAR-PEDIDO] ✅ PASO 5: Pedido base creado', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'tiempo_ms' => $tiempoPaso5,
            ]);

            // ====== PASO 6: Crear carpetas ======
            $inicioPaso6 = microtime(true);
            $this->crearCarpetasPedido($pedidoId);
            $tiempoPaso6 = round((microtime(true) - $inicioPaso6) * 1000, 2);

            Log::info('[CREAR-PEDIDO] ✅ PASO 6: Carpetas creadas', [
                'pedido_id' => $pedidoId,
                'tiempo_ms' => $tiempoPaso6,
            ]);

            // ====== PASO 7: CRÍTICO - Mapear y procesar imágenes ======
            $inicioPaso7 = microtime(true);
            $this->mapeoImagenes->mapearYCrearFotos(
                $dtoPedido,      // DTO con prendas normalizadas
                $pedidoId,       // ID del pedido
                $request         // Request con archivos
            );
            $tiempoPaso7 = round((microtime(true) - $inicioPaso7) * 1000, 2);

            Log::info('[CREAR-PEDIDO] ✅ PASO 7: Imágenes mapeadas y creadas', [
                'pedido_id' => $pedidoId,
                'imagenes_mapeadas' => count($dtoPedido->imagen_uid_a_ruta),
                'tiempo_ms' => $tiempoPaso7,
            ]);

            // ====== PASO 7B: CRÍTICO - Procesar imágenes de EPPs ======
            $tiempoPaso7B = 0;
            if (!empty($dtoPedido->epps)) {
                $inicioPaso7B = microtime(true);
                $this->procesarYAsignarEpps($request, $pedidoId, $dtoPedido->epps);
                $tiempoPaso7B = round((microtime(true) - $inicioPaso7B) * 1000, 2);
                
                Log::info('[CREAR-PEDIDO] ✅ PASO 7B: Imágenes de EPPs procesadas', [
                    'pedido_id' => $pedidoId,
                    'epps_count' => count($dtoPedido->epps),
                    'tiempo_ms' => $tiempoPaso7B,
                ]);
            }

            // ====== PASO 8: Calcular cantidades y commit ======
            $inicioPaso8 = microtime(true);
            $cantidadTotalPrendas = $this->calcularCantidadTotalPrendas($pedidoId);
            $cantidadTotalEpps = $this->calcularCantidadTotalEpps($pedidoId);
            $cantidadTotal = $cantidadTotalPrendas + $cantidadTotalEpps;
            $pedido->update(['cantidad_total' => $cantidadTotal]);

            DB::commit();
            $tiempoPaso8 = round((microtime(true) - $inicioPaso8) * 1000, 2);

            $tiempoTotal = round((microtime(true) - $inicioTotal) * 1000, 2);
            
            Log::info('[CREAR-PEDIDO] ✨ TRANSACCIÓN EXITOSA - RESUMEN TOTAL', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'cantidad_total_prendas' => $cantidadTotalPrendas,
                'cantidad_total_epps' => $cantidadTotalEpps,
                'cantidad_total' => $cantidadTotal,
                'tiempo_total_ms' => $tiempoTotal,
                'desglose_pasos' => [
                    'paso_1_json_ms' => $tiempoPaso1,
                    'paso_2_cliente_ms' => $tiempoPaso2,
                    'paso_3_dto_ms' => $tiempoPaso3,
                    'paso_5_pedido_base_ms' => $tiempoPaso5,
                    'paso_6_carpetas_ms' => $tiempoPaso6,
                    'paso_7_imagenes_ms' => $tiempoPaso7,
                    'paso_7b_epps_ms' => $tiempoPaso7B,
                    'paso_8_calculo_ms' => $tiempoPaso8,
                ],
                'resumen' => "JSON: {$tiempoPaso1}ms | Cliente: {$tiempoPaso2}ms | DTO: {$tiempoPaso3}ms | PedidoBase: {$tiempoPaso5}ms | Carpetas: {$tiempoPaso6}ms | Imágenes: {$tiempoPaso7}ms | EPPs: {$tiempoPaso7B}ms | Cálculo: {$tiempoPaso8}ms | TOTAL: {$tiempoTotal}ms"
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente_id' => $cliente->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('[CrearPedidoEditableController] ERROR - Rollback iniciado', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Limpiar archivos si se creó carpeta
            if ($pedidoId) {
                try {
                    $carpetaPedido = "pedidos/{$pedidoId}";
                    if (Storage::disk('public')->exists($carpetaPedido)) {
                        Storage::disk('public')->deleteDirectory($carpetaPedido);
                        Log::info('[CrearPedidoEditableController] Carpeta eliminada', [
                            'carpeta' => $carpetaPedido,
                        ]);
                    }
                } catch (\Exception $cleanupError) {
                    Log::error('[CrearPedidoEditableController] Error al limpiar archivos', [
                        'error' => $cleanupError->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar y asignar imágenes directamente a carpetas finales
     * 
     * 1 archivo = 1 webp en su carpeta final
     * NO temp, NO relocalización
     * Carpetas específicas por tipo:
     *    - pedidos/{id}/prendas/
     *    - pedidos/{id}/telas/
     *    - pedidos/{id}/procesos/{TIPO}/
     * 
     * @param Request $request
     * @param int $pedidoId
     * @param array $items
     */
    private function procesarYAsignarImagenes(Request $request, int $pedidoId, array $items): void
    {
        Log::info('[CrearPedidoEditableController] 📸 Procesando imágenes en carpetas finales', [
            'pedido_id' => $pedidoId,
            'items_count' => count($items),
        ]);

        // Obtener pedido con relaciones
        $pedido = \App\Models\PedidoProduccion::with('prendas.procesos.tipoProceso', 'prendas.coloresTelas')->findOrFail($pedidoId);
        $prendas = $pedido->prendas;

        foreach ($items as $itemIdx => $item) {
            if (!isset($prendas[$itemIdx])) {
                Log::warning('[CrearPedidoEditableController] Prenda no encontrada', ['prenda_idx' => $itemIdx]);
                continue;
            }

            $prenda = $prendas[$itemIdx];

            // ==================== PRENDAS ====================
            $imgIdx = 0;
            while (true) {
                $formKey = "prendas.{$itemIdx}.imagenes.{$imgIdx}";
                if (!$request->hasFile($formKey)) {
                    break;
                }

                $archivo = $request->file($formKey);
                $resultado = $this->imageUploadService->guardarImagenDirecta(
                    $archivo, $pedidoId, 'prendas', null, null
                );

                \App\Models\PrendaFotoPedido::create([
                    'prenda_pedido_id' => $prenda->id,
                    'ruta_webp' => $resultado['webp'],
                    'orden' => $imgIdx + 1,
                ]);

                Log::debug('[CrearPedidoEditableController] 📸 Imagen prenda guardada', [
                    'prenda_id' => $prenda->id,
                    'webp' => $resultado['webp'],
                ]);

                $imgIdx++;
            }

            // ==================== TELAS ====================
            if (isset($item['telas']) && is_array($item['telas'])) {
                Log::info('[CrearPedidoEditableController] 🧵 Procesando telas', [
                    'prenda_id' => $prenda->id,
                    'cantidad_telas' => count($item['telas']),
                ]);

                // Recargar relaciones de telas para asegurar que están actualizadas
                $telasRelacion = $prenda->coloresTelas()->get();
                
                Log::debug('[CrearPedidoEditableController] Telas existentes en BD', [
                    'cantidad' => $telasRelacion->count(),
                    'ids' => $telasRelacion->pluck('id')->toArray(),
                ]);
                
                foreach ($item['telas'] as $telaIdx => $tela) {
                    // ✅ MEJORADO: Obtener o crear la relación de tela
                    $telaRelacion = $telasRelacion->get($telaIdx);
                    
                    if (!$telaRelacion) {
                        Log::warning('[CrearPedidoEditableController] ⚠️ Tela no encontrada en índice', [
                            'prenda_id' => $prenda->id,
                            'tela_idx' => $telaIdx,
                            'datos_tela' => $tela,
                        ]);
                        
                        // ✅ MEJORADO: Procesar nombres de color y tela usando ColorTelaService
                        if (!empty($tela) && isset($tela['color']) && isset($tela['tela'])) {
                            try {
                                // Convertir nombres a IDs
                                $colorId = $this->colorTelaService->obtenerOCrearColor($tela['color']);
                                $telaId = $this->colorTelaService->obtenerOCrearTela($tela['tela']);
                                
                                Log::info('[CrearPedidoEditableController]  Color/Tela procesados', [
                                    'color_nombre' => $tela['color'],
                                    'color_id' => $colorId,
                                    'tela_nombre' => $tela['tela'],
                                    'tela_id' => $telaId,
                                ]);
                                
                                if ($colorId && $telaId) {
                                    $colorTelaId = $this->colorTelaService->obtenerOCrearColorTela(
                                        $prenda->id,
                                        $colorId,
                                        $telaId
                                    );
                                    
                                    if ($colorTelaId) {
                                        $telaRelacion = \App\Models\PrendaPedidoColorTela::find($colorTelaId);
                                        
                                        Log::info('[CrearPedidoEditableController] ✅ Tela obtenida/creada', [
                                            'color_tela_id' => $colorTelaId,
                                            'color_id' => $colorId,
                                            'tela_id' => $telaId,
                                            'referencia' => $tela['referencia'] ?? '',
                                        ]);
                                        
                                        // Actualizar referencia si existe
                                        if (isset($tela['referencia']) && !empty($tela['referencia'])) {
                                            $telaRelacion->referencia = $tela['referencia'];
                                            $telaRelacion->save();
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::error('[CrearPedidoEditableController] ❌ Error al procesar color/tela', [
                                    'error' => $e->getMessage(),
                                    'tela_data' => $tela,
                                ]);
                                continue;
                            }
                        } else {
                            Log::debug('[CrearPedidoEditableController] ⚠️ Datos de tela incompletos', [
                                'tela_data' => $tela,
                            ]);
                            continue;
                        }
                    }

                    // ==================== PROCESAR IMÁGENES DE TELA ====================
                    if (!$telaRelacion) {
                        Log::warning('[CrearPedidoEditableController] ⚠️ No se pudo obtener/crear relación de tela');
                        continue;
                    }

                    $imgIdx = 0;
                    $imagenesGuardadas = 0;
                    
                    while (true) {
                        $formKey = "prendas.{$itemIdx}.telas.{$telaIdx}.imagenes.{$imgIdx}";
                        if (!$request->hasFile($formKey)) {
                            break;
                        }

                        $archivo = $request->file($formKey);
                        $resultado = $this->imageUploadService->guardarImagenDirecta(
                            $archivo, $pedidoId, 'telas', null, null
                        );

                        \App\Models\PrendaFotoTelaPedido::create([
                            'prenda_pedido_colores_telas_id' => $telaRelacion->id,
                            'ruta_webp' => $resultado['webp'],
                            'orden' => $imgIdx + 1,
                        ]);

                        Log::debug('[CrearPedidoEditableController] 📸 Imagen tela guardada', [
                            'tela_relacion_id' => $telaRelacion->id,
                            'webp' => $resultado['webp'],
                            'orden' => $imgIdx + 1,
                        ]);

                        $imagenesGuardadas++;
                        $imgIdx++;
                    }
                    
                    if ($imagenesGuardadas > 0) {
                        Log::info('[CrearPedidoEditableController] ✅ Imágenes de tela procesadas', [
                            'tela_id' => $telaRelacion->id,
                            'cantidad_imagenes' => $imagenesGuardadas,
                        ]);
                    } else {
                        Log::debug('[CrearPedidoEditableController] ℹ️ Sin imágenes para esta tela', [
                            'tela_id' => $telaRelacion->id,
                        ]);
                    }
                }
            }

            // ==================== PROCESOS ====================
            if (isset($item['procesos']) && is_array($item['procesos'])) {
                foreach ($item['procesos'] as $procesoKey => $proceso) {
                    $nombreProceso = strtoupper($proceso['nombre'] ?? $procesoKey);
                    
                    // Buscar proceso en BD
                    $procesoDetalle = $prenda->procesos()->whereHas('tipoProceso', function($q) use ($nombreProceso) {
                        $q->where('nombre', $nombreProceso);
                    })->first();

                    if (!$procesoDetalle) {
                        Log::warning('[CrearPedidoEditableController] Proceso no encontrado', [
                            'prenda_id' => $prenda->id,
                            'proceso' => $nombreProceso,
                        ]);
                        continue;
                    }

                    $imgIdx = 0;
                    while (true) {
                        $formKey = "prendas.{$itemIdx}.procesos.{$procesoKey}.imagenes.{$imgIdx}";
                        if (!$request->hasFile($formKey)) {
                            break;
                        }

                        $archivo = $request->file($formKey);
                        $resultado = $this->imageUploadService->guardarImagenDirecta(
                            $archivo, $pedidoId, 'procesos', $nombreProceso, null
                        );

                        \App\Models\PedidosProcessImagenes::create([
                            'proceso_prenda_detalle_id' => $procesoDetalle->id,
                            'ruta_webp' => $resultado['webp'],
                            'orden' => $imgIdx + 1,
                            'es_principal' => $imgIdx === 0 ? 1 : 0,
                        ]);

                        Log::debug('[CrearPedidoEditableController] 📸 Imagen proceso guardada', [
                            'proceso_id' => $procesoDetalle->id,
                            'tipo' => $nombreProceso,
                            'webp' => $resultado['webp'],
                        ]);

                        $imgIdx++;
                    }
                }
            }
        }

        Log::info('[CrearPedidoEditableController] Todas las imágenes procesadas y asignadas');
    }

    /**
     * Procesar y asignar EPPs al pedido
     * 
     * 1 archivo EPP = 1 webp en su carpeta final
     * Carpeta: pedidos/{id}/epps/{epp_id}/
     * Crea registros en pedido_epp e pedido_epp_imagenes
     * 
     * Estructura esperada:
     * - epps[i][epp_id]: ID del EPP catálogo
     * - epps[i][nombre_epp]: Nombre descriptivo
     * - epps[i][cantidad]: Cantidad solicitada
     * - epps[i][observaciones]: Notas opcionales
     * - epps[i][imagenes][j]: Archivos de imagen
     * 
     * @param Request $request
     * @param int $pedidoId
     * @param array $epps
     */
    private function procesarYAsignarEpps(Request $request, int $pedidoId, array $epps): void
    {
        Log::info('[CrearPedidoEditableController] 📦 Procesando EPPs', [
            'pedido_id' => $pedidoId,
            'epps_count' => count($epps),
        ]);

        foreach ($epps as $eppIdx => $eppData) {
            // Validar estructura mínima
            if (empty($eppData['epp_id'])) {
                Log::warning('[CrearPedidoEditableController] EPP sin epp_id', [
                    'epp_idx' => $eppIdx,
                    'eppData' => $eppData,
                ]);
                continue;
            }

            // Verificar que el EPP existe en la tabla epps
            $eppCatalogo = \DB::table('epps')
                ->where('id', $eppData['epp_id'])
                ->first();

            if (!$eppCatalogo) {
                Log::warning('[CrearPedidoEditableController] EPP no encontrado en catálogo', [
                    'epp_id' => $eppData['epp_id'],
                ]);
                continue;
            }

            // Crear registro en pedido_epp
            //  NOTA: La tabla pedido_epp NO tiene columna nombre_epp
            // Solo guarda: pedido_produccion_id, epp_id, cantidad, observaciones
            $pedidoEpp = PedidoEpp::create([
                'pedido_produccion_id' => $pedidoId,
                'epp_id' => $eppData['epp_id'],
                'cantidad' => $eppData['cantidad'] ?? 1,
                'observaciones' => $eppData['observaciones'] ?? null,
            ]);

            Log::info('[CrearPedidoEditableController] EPP creado', [
                'pedido_epp_id' => $pedidoEpp->id,
                'epp_id' => $eppData['epp_id'],
                'cantidad' => $eppData['cantidad'] ?? 1,
            ]);

            // ==================== IMÁGENES EPP ====================
            $imgIdx = 0;
            while (true) {
                $formKey = "epps.{$eppIdx}.imagenes.{$imgIdx}";
                if (!$request->hasFile($formKey)) {
                    break;
                }

                try {
                    $archivo = $request->file($formKey);
                    
                    // Guardar imagen en carpeta del EPP con conversión a WebP
                    $resultado = $this->imageUploadService->guardarImagenDirecta(
                        $archivo, 
                        $pedidoId, 
                        'epps',          // tipo
                        null,            // subcarpeta
                        "epp_{$eppData['epp_id']}_img_{$imgIdx}"
                    );

                    // Crear registro en pedido_epp_imagenes
                    // NOTAS:
                    // - ruta_original: ruta sin procesar (en este caso es la misma WebP)
                    // - ruta_web: ruta accesible desde el navegador (también WebP)
                    // - principal: 1 si es la primera imagen, 0 si no
                    PedidoEppImagen::create([
                        'pedido_epp_id' => $pedidoEpp->id,
                        'ruta_original' => $resultado['webp'],  // Convertida a WebP
                        'ruta_web' => $resultado['webp'],       // Convertida a WebP
                        'orden' => $imgIdx + 1,
                        'principal' => $imgIdx === 0 ? 1 : 0,   // Primera imagen es principal
                    ]);

                    Log::debug('[CrearPedidoEditableController] 📸 Imagen EPP guardada (WebP)', [
                        'pedido_epp_id' => $pedidoEpp->id,
                        'webp' => $resultado['webp'],
                        'orden' => $imgIdx + 1,
                    ]);

                    $imgIdx++;
                } catch (\Exception $e) {
                    Log::error('[CrearPedidoEditableController] Error procesando imagen EPP', [
                        'pedido_epp_id' => $pedidoEpp->id,
                        'form_key' => $formKey,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }

            if ($imgIdx === 0) {
                Log::warning('[CrearPedidoEditableController] EPP sin imágenes', [
                    'pedido_epp_id' => $pedidoEpp->id,
                    'epp_id' => $eppData['epp_id'],
                ]);
            } else {
                Log::info('[CrearPedidoEditableController] Imágenes EPP procesadas', [
                    'pedido_epp_id' => $pedidoEpp->id,
                    'imagenes_count' => $imgIdx,
                ]);
            }
        }

        Log::info('[CrearPedidoEditableController] Todos los EPPs procesados exitosamente', [
            'pedido_id' => $pedidoId,
            'epps_count' => count($epps),
        ]);
    }

    /**
     * @deprecated Método obsoleto - usar procesarYAsignarImagenes()
     */
    private function procesarArchivosUnaVez(Request $request, ?int $pedidoId, array $items): array
    {
        $mapaImagenes = [];
        $carpetaBase = $pedidoId ? "pedidos/{$pedidoId}" : "temp/" . uniqid('upload_');

        Log::info('[CrearPedidoEditableController] 📸 Procesando archivos físicos UNA SOLA VEZ', [
            'carpeta' => $carpetaBase,
            'items_count' => count($items),
        ]);

        foreach ($items as $itemIdx => $item) {
            // ==================== PRENDAS ====================
            $imgIdx = 0;
            while (true) {
                $formKey = "prendas.{$itemIdx}.imagenes.{$imgIdx}";
                if (!$request->hasFile($formKey)) {
                    break;
                }

                $archivo = $request->file($formKey);
                $resultado = $this->imageUploadService->guardarImagenDirecta(
                    $archivo, $pedidoId ?? 0, 'imagenes', null, null
                );

                $mapaImagenes[$formKey] = [
                    'tipo' => 'prenda',
                    'prenda_idx' => $itemIdx,
                    'ruta_original' => $resultado['original'],
                    'ruta_webp' => $resultado['webp'],
                    'ruta_thumb' => $resultado['thumbnail'],
                ];

                Log::debug('[CrearPedidoEditableController] 📸 Archivo procesado', [
                    'form_key' => $formKey,
                    'tipo' => 'prenda',
                    'webp' => $resultado['webp'],
                ]);

                $imgIdx++;
            }

            // ==================== TELAS ====================
            if (isset($item['telas']) && is_array($item['telas'])) {
                foreach ($item['telas'] as $telaIdx => $tela) {
                    $imgIdx = 0;
                    while (true) {
                        $formKey = "prendas.{$itemIdx}.telas.{$telaIdx}.imagenes.{$imgIdx}";
                        if (!$request->hasFile($formKey)) {
                            break;
                        }

                        $archivo = $request->file($formKey);
                        $resultado = $this->imageUploadService->guardarImagenDirecta(
                            $archivo, $pedidoId ?? 0, 'imagenes', null, null
                        );

                        $mapaImagenes[$formKey] = [
                            'tipo' => 'tela',
                            'prenda_idx' => $itemIdx,
                            'tela_idx' => $telaIdx,
                            'ruta_original' => $resultado['original'],
                            'ruta_webp' => $resultado['webp'],
                            'ruta_thumb' => $resultado['thumbnail'],
                        ];

                        Log::debug('[CrearPedidoEditableController] 📸 Archivo procesado', [
                            'form_key' => $formKey,
                            'tipo' => 'tela',
                            'webp' => $resultado['webp'],
                        ]);

                        $imgIdx++;
                    }
                }
            }

            // ==================== PROCESOS ====================
            if (isset($item['procesos']) && is_array($item['procesos'])) {
                foreach ($item['procesos'] as $procesoKey => $proceso) {
                    $nombreProceso = $proceso['nombre'] ?? $procesoKey;
                    $imgIdx = 0;
                    
                    while (true) {
                        $formKey = "prendas.{$itemIdx}.procesos.{$procesoKey}.imagenes.{$imgIdx}";
                        if (!$request->hasFile($formKey)) {
                            break;
                        }

                        $archivo = $request->file($formKey);
                        $resultado = $this->imageUploadService->guardarImagenDirecta(
                            $archivo, $pedidoId ?? 0, 'imagenes', null, null
                        );

                        $mapaImagenes[$formKey] = [
                            'tipo' => 'proceso',
                            'prenda_idx' => $itemIdx,
                            'proceso_key' => $procesoKey,
                            'proceso_nombre' => strtoupper($nombreProceso),
                            'ruta_original' => $resultado['original'],
                            'ruta_webp' => $resultado['webp'],
                            'ruta_thumb' => $resultado['thumbnail'],
                        ];

                        Log::debug('[CrearPedidoEditableController] 📸 Archivo procesado', [
                            'form_key' => $formKey,
                            'tipo' => 'proceso',
                            'nombre' => $nombreProceso,
                            'webp' => $resultado['webp'],
                        ]);

                        $imgIdx++;
                    }
                }
            }
        }

        Log::info('[CrearPedidoEditableController] Archivos procesados', [
            'total_archivos' => count($mapaImagenes),
            'tipos' => array_count_values(array_column($mapaImagenes, 'tipo')),
        ]);

        return $mapaImagenes;
    }

    /**
     * Mover imágenes de temp/ a pedidos/{id}/ y actualizar BD
     * 
     * @param int $pedidoId
     * @param array $mapaImagenes
     */
    private function relocalizarImagenesAPedido(int $pedidoId, array $mapaImagenes): void
    {
        if (empty($mapaImagenes)) {
            return;
        }

        Log::info('[CrearPedidoEditableController] 🚚 Relocalizando imágenes', [
            'pedido_id' => $pedidoId,
            'imagenes_count' => count($mapaImagenes),
        ]);

        // Obtener pedido y prendas
        $pedido = \App\Models\PedidoProduccion::with('prendas.procesos', 'prendas.coloresTelas')->findOrFail($pedidoId);
        $prendas = $pedido->prendas;

        foreach ($mapaImagenes as $formKey => $info) {
            $prendaIdx = $info['prenda_idx'];
            
            if (!isset($prendas[$prendaIdx])) {
                Log::warning('[CrearPedidoEditableController] Prenda no encontrada', [
                    'prenda_idx' => $prendaIdx,
                    'form_key' => $formKey,
                ]);
                continue;
            }

            $prenda = $prendas[$prendaIdx];

            // Mover archivos de pedidos/0/ a pedidos/{pedidoId}/
            $rutaOriginalOld = $info['ruta_original'];
            $rutaWebpOld = $info['ruta_webp'];
            $rutaThumbOld = $info['ruta_thumb'];

            $rutaOriginalNew = str_replace('pedidos/0/', "pedidos/{$pedidoId}/", $rutaOriginalOld);
            $rutaWebpNew = str_replace('pedidos/0/', "pedidos/{$pedidoId}/", $rutaWebpOld);
            $rutaThumbNew = str_replace('pedidos/0/', "pedidos/{$pedidoId}/", $rutaThumbOld);

            // Crear directorio destino
            $dirDestino = dirname(storage_path("app/public/{$rutaWebpNew}"));
            if (!file_exists($dirDestino)) {
                mkdir($dirDestino, 0755, true);
            }

            // Mover archivos
            Storage::disk('public')->move($rutaOriginalOld, $rutaOriginalNew);
            Storage::disk('public')->move($rutaWebpOld, $rutaWebpNew);
            Storage::disk('public')->move($rutaThumbOld, $rutaThumbNew);

            // Asignar según tipo
            if ($info['tipo'] === 'prenda') {
                \App\Models\PrendaFotoPedido::create([
                    'prenda_pedido_id' => $prenda->id,
                    'ruta_original' => $rutaOriginalNew,
                    'ruta_webp' => $rutaWebpNew,
                ]);
            }
            elseif ($info['tipo'] === 'tela') {
                $telaIdx = $info['tela_idx'] ?? 0;
                
                // ✅ MEJORADO: Recargar y verificar relaciones de telas
                $telasRelacion = $prenda->coloresTelas()->get();
                $telaRelacion = $telasRelacion->get($telaIdx);
                
                if ($telaRelacion) {
                    \App\Models\PrendaFotoTelaPedido::create([
                        'prenda_pedido_colores_telas_id' => $telaRelacion->id,
                        'ruta_original' => $rutaOriginalNew,
                        'ruta_webp' => $rutaWebpNew,
                    ]);
                } else {
                    Log::warning('[CrearPedidoEditableController] Tela no encontrada para foto', [
                        'prenda_id' => $prenda->id,
                        'tela_idx' => $telaIdx,
                        'tela_relacion_count' => $telasRelacion->count(),
                    ]);
                }
            }
            elseif ($info['tipo'] === 'proceso') {
                $procesoNombre = $info['proceso_nombre'];
                $proceso = $prenda->procesos()->whereHas('tipoProceso', function($q) use ($procesoNombre) {
                    $q->where('nombre', $procesoNombre);
                })->first();

                if ($proceso) {
                    \App\Models\PedidosProcessImagenes::create([
                        'proceso_prenda_detalle_id' => $proceso->id,
                        'ruta_original' => $rutaOriginalNew,
                        'ruta_webp' => $rutaWebpNew,
                    ]);
                }
            }
        }

        // Eliminar carpeta temp/
        Storage::disk('public')->deleteDirectory('pedidos/0');

        Log::info('[CrearPedidoEditableController] Imágenes relocalizadas exitosamente');
    }

    /**
     * Procesar FormData y validar
     * 
     * NUEVA ARQUITECTURA:
     * 1. Decodificar "pedido" JSON string
     * 2. Procesar archivos desde rutas prendas[i][imagenes][j], etc.
     * 3. Inyectar rutas en estructura pedido
     * 4. Validar estructura completa
     * 
     * @param Request $request
     * @return array
     */
    private function procesarArchivosYValidar(Request $request): array
    {
        // PASO 1: Decodificar metadata JSON
        $pedidoJSON = $request->input('pedido');
        if (!$pedidoJSON) {
            throw new \Exception('Campo "pedido" JSON requerido en FormData');
        }

        $pedido = json_decode($pedidoJSON, true);
        if (!$pedido) {
            throw new \Exception('JSON inválido en campo "pedido"');
        }

        \Log::info('[CrearPedidoEditableController] Metadata decodificada', [
            'cliente' => $pedido['cliente'],
            'items_count' => count($pedido['items'] ?? []),
            'all_files' => array_keys($request->allFiles()),
        ]);

        // PASO 2: Procesar archivos e inyectarlos en pedido
        if (!isset($pedido['items']) || !is_array($pedido['items'])) {
            $pedido['items'] = [];
        }

        foreach ($pedido['items'] as $itemIdx => &$item) {
            // -------- IMÁGENES DE PRENDA --------
            $imagenesPrend = [];
            $imgIdx = 0;
            while (true) {
                $archivoKey = "prendas.{$itemIdx}.imagenes.{$imgIdx}";
                if (!$request->hasFile($archivoKey)) {
                    break;
                }
                $archivo = $request->file($archivoKey);
                $ruta = $this->guardarImagen($archivo, 'prendas');
                $imagenesPrend[] = $ruta;
                $imgIdx++;
            }
            if (!empty($imagenesPrend)) {
                $item['imagenes'] = $imagenesPrend;
            }

            // -------- IMÁGENES DE TELAS --------
            if (isset($item['telas']) && is_array($item['telas'])) {
                foreach ($item['telas'] as $telaIdx => &$tela) {
                    $imagenesTela = [];
                    $imgIdx = 0;
                    while (true) {
                        $archivoKey = "prendas.{$itemIdx}.telas.{$telaIdx}.imagenes.{$imgIdx}";
                        if (!$request->hasFile($archivoKey)) {
                            break;
                        }
                        $archivo = $request->file($archivoKey);
                        $ruta = $this->guardarImagen($archivo, 'telas');
                        $imagenesTela[] = $ruta;
                        $imgIdx++;
                    }
                    if (!empty($imagenesTela)) {
                        $tela['imagenes'] = $imagenesTela;
                    }
                }
            }

            // -------- IMÁGENES DE PROCESOS --------
            if (isset($item['procesos']) && is_array($item['procesos'])) {
                foreach ($item['procesos'] as $procesoKey => &$proceso) {
                    $imagenesProceso = [];
                    $imgIdx = 0;
                    while (true) {
                        $archivoKey = "prendas.{$itemIdx}.procesos.{$procesoKey}.imagenes.{$imgIdx}";
                        if (!$request->hasFile($archivoKey)) {
                            break;
                        }
                        $archivo = $request->file($archivoKey);
                        $ruta = $this->guardarImagen($archivo, 'procesos');
                        $imagenesProceso[] = $ruta;
                        $imgIdx++;
                    }
                    // Inyectar directamente en proceso['imagenes'], no en datos
                    if (!empty($imagenesProceso)) {
                        $proceso['imagenes'] = $imagenesProceso;
                    }
                }
            }
        }

        \Log::info('[CrearPedidoEditableController] Archivos procesados', [
            'items_with_images' => count(array_filter($pedido['items'], fn($i) => isset($i['imagenes']))),
            'archivos_totales' => count($request->allFiles()),
        ]);

        // VERIFICACIÓN CRÍTICA: Verificar estructura de items
        foreach ($pedido['items'] as $itemIdx => $item) {
            $telaCount = isset($item['telas']) && is_array($item['telas']) ? count($item['telas']) : 0;
            $procesosKeys = isset($item['procesos']) && is_array($item['procesos']) ? array_keys($item['procesos']) : [];
            
            \Log::info('[CrearPedidoEditableController] 🔍 Estructura item ' . $itemIdx, [
                'nombre_prenda' => $item['nombre_prenda'] ?? 'SIN NOMBRE',
                'tiene_telas' => $telaCount > 0 ? 'SÍ (' . $telaCount . ')' : 'NO',
                'telas_estructura' => $telaCount > 0 ? array_keys($item['telas'][0] ?? []) : [],
                'tiene_procesos' => count($procesosKeys) > 0 ? 'SÍ' : 'NO',
                'procesos_keys' => $procesosKeys,
                'procesos_estructura' => count($procesosKeys) > 0 ? array_keys($item['procesos'][$procesosKeys[0]] ?? []) : [],
            ]);
        }

        // PASO 3: Retornar pedido con archivos inyectados
        return [
            'cliente' => $pedido['cliente'],
            'asesora' => $pedido['asesora'],
            'forma_de_pago' => $pedido['forma_de_pago'],
            'descripcion' => $pedido['descripcion'] ?? '',
            'items' => $pedido['items'],
        ];
    }

    /**
     * Guardar imagen y retornar ruta webp
     * Procesa imagen a WebP y guarda en temp
     * 
     * @param \Illuminate\Http\UploadedFile $archivo
     * @param string $carpeta
     * @return string Ruta webp para relocalizar después
     */
    private function guardarImagen($archivo, string $carpeta): string
    {
        // Generar UUID para agrupar uploads temporales
        $tempUuid = \Illuminate\Support\Str::uuid()->toString();
        
        // Generar nombre único
        $filename = time() . '_' . uniqid();
        
        // Procesar imagen (original + WebP + thumbnail)
        $resultado = $this->imageUploadService->processAndSaveImage(
            $archivo,
            $filename,
            $carpeta,
            $tempUuid
        );
        
        // Retornar ruta WebP (será relocalizada después)
        return $resultado['webp'];
    }

    /**
     * NUEVO ENDPOINT: Subir imagen directamente a pedidos/{pedido_id}/{tipo}/
     * POST /asesores/pedidos-editable/subir-imagen
     * 
     * Requiere pedido_id existente
     * Guarda directamente sin pasos intermedios
     * Soporta subcarpetas (para procesos)
     * 
     * Body:
     * - imagen: file (required)
     * - pedido_id: int (required)
     * - tipo: string (required) - 'prendas', 'telas', 'procesos'
     * - subcarpeta: string (optional) - ej: 'ESTAMPADO' para procesos
     * - filename: string (optional) - nombre personalizado
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function subirImagen(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'imagen' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240', // 10MB
                'pedido_id' => 'required|integer|exists:pedidos,id',
                'tipo' => 'required|string|in:prendas,telas,procesos,logos,reflectivos',
                'subcarpeta' => 'nullable|string|max:100',
                'filename' => 'nullable|string|max:100',
            ]);

            // Guardar imagen directamente en pedidos/{pedido_id}/{tipo}/
            $resultado = $this->imageUploadService->guardarImagenDirecta(
                $request->file('imagen'),
                $validated['pedido_id'],
                $validated['tipo'],
                $validated['subcarpeta'] ?? null,
                $validated['filename'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Imagen guardada exitosamente',
                'data' => [
                    'ruta_original' => $resultado['original'],
                    'ruta_webp' => $resultado['webp'],
                    'thumbnail' => $resultado['thumbnail'],
                    'url_webp' => Storage::url($resultado['webp']),
                    'url_original' => Storage::url($resultado['original']),
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[CrearPedidoEditableController] Error subiendo imagen directa', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al subir imagen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @deprecated Usar subirImagen() con pedido_id en su lugar
     */
    public function subirImagenesPrenda(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'imagenes' => 'required|array|min:1',
                'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
                'temp_uuid' => 'nullable|string',
            ]);

            // Generar UUID si no está presente (agrupa uploads del mismo lote)
            $tempUuid = $request->input('temp_uuid') ?? \Illuminate\Support\Str::uuid()->toString();
            
            $uploadedPaths = [];
            $prendaIndex = 0; // Se usa para generar nombre única

            foreach ($request->file('imagenes') as $imagen) {
                $this->imageUploadService->validateImage($imagen);
                
                $result = $this->imageUploadService->uploadPrendaImage(
                    $imagen,
                    $prendaIndex,
                    null,
                    $tempUuid
                );

                $uploadedPaths[] = [
                    'ruta_webp' => $result['ruta_webp'],
                    'ruta_original' => $result['ruta_original'],
                    'url' => $result['url'],
                    'thumbnail' => $result['thumbnail'],
                ];

                $prendaIndex++;
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedPaths) . ' imagen(es) subida(s) temporalmente',
                'imagenes' => $uploadedPaths,
                'temp_uuid' => $tempUuid,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir imágenes: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Calcular cantidad total de prendas
     * Suma todas las cantidades de tallas de todas las prendas del pedido
     * 
     * @param int $pedidoId
     * @return int
     */
    private function calcularCantidadTotalPrendas(int $pedidoId): int
    {
        try {
            // SOLUCIÓN: Usar tabla actual pedidos_procesos_prenda_tallas
            // Relación: pedido → prenda → proceso → tallas
            
            $cantidad = DB::table('pedidos_procesos_prenda_tallas as pppt')
                ->selectRaw('COALESCE(SUM(pppt.cantidad), 0) as total')
                ->join('pedidos_procesos_prenda_detalles as ppd', 'pppt.proceso_prenda_detalle_id', '=', 'ppd.id')
                ->join('prendas_pedido as pp', 'ppd.prenda_pedido_id', '=', 'pp.id')
                ->where('pp.pedido_produccion_id', $pedidoId)
                ->value('total');

            Log::debug('[CrearPedidoEditableController] calcularCantidadTotalPrendas - Éxito', [
                'pedido_id' => $pedidoId,
                'cantidad_total' => (int)$cantidad,
                'metodo' => 'pedidos_procesos_prenda_tallas',
            ]);

            return (int) $cantidad ?? 0;
        } catch (\Exception $e) {
            Log::warning('[CrearPedidoEditableController] calcularCantidadTotalPrendas - Error', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Calcular cantidad total de EPPs
     * Suma todas las cantidades de todos los EPPs del pedido
     * 
     * @param int $pedidoId
     * @return int
     */
    private function calcularCantidadTotalEpps(int $pedidoId): int
    {
        $cantidad = DB::table('pedido_epp')
            ->where('pedido_produccion_id', $pedidoId)
            ->sum('cantidad');

        return (int) $cantidad;
    }

    /**
     * ✅ Crear estructura de carpetas para un pedido
     * 
     * Crea:
     * - storage/app/public/pedidos/{pedido_id}/prendas/
     * - storage/app/public/pedidos/{pedido_id}/telas/
     * - storage/app/public/pedidos/{pedido_id}/procesos/
     * - storage/app/public/pedidos/{pedido_id}/epps/
     * 
     * @param int $pedidoId
     * @return void
     */
    private function crearCarpetasPedido(int $pedidoId): void
    {
        $basePath = "pedidos/{$pedidoId}";
        // IMPORTANTE: Usar singular para coincidir con ImageUploadService::guardarImagenDirecta()
        // que convierte plural a singular con rtrim($tipo, 's')
        // Ejemplos: prendas → prenda, telas → tela, procesos → proceso
        $carpetas = ['prenda', 'tela', 'proceso', 'epp'];

        foreach ($carpetas as $carpeta) {
            $rutaCompleta = "{$basePath}/{$carpeta}";
            
            if (!Storage::disk('public')->exists($rutaCompleta)) {
                try {
                    Storage::disk('public')->makeDirectory($rutaCompleta, 0755, true);
                    Log::info('[CrearPedidoEditableController] Carpeta creada', [
                        'pedido_id' => $pedidoId,
                        'carpeta' => $rutaCompleta
                    ]);
                } catch (\Exception $e) {
                    Log::warning('[CrearPedidoEditableController] Error creando carpeta', [
                        'pedido_id' => $pedidoId,
                        'carpeta' => $rutaCompleta,
                        'error' => $e->getMessage()
                    ]);
                    // No fallar por carpetas
                }
            }
        }
    }
    
    /**
     * Buscar archivos anidados en la estructura de inputs
     * Los archivos con claves como "prendas[0][imagenes][0]" necesitan búsqueda recursiva
     */
    private function buscarArchivosAnidados($datos, $prefijo = '', &$archivos = []): void
    {
        foreach ($datos as $key => $valor) {
            $nuevaPrefijo = $prefijo ? "{$prefijo}[{$key}]" : $key;
            
            if ($valor instanceof \Illuminate\Http\UploadedFile) {
                $archivos[] = [
                    'key' => $nuevaPrefijo,
                    'name' => $valor->getClientOriginalName(),
                    'size' => $valor->getSize()
                ];
            } elseif (is_array($valor)) {
                $this->buscarArchivosAnidados($valor, $nuevaPrefijo, $archivos);
            }
        }
    }

    /**
     * Validar que el JSON del frontend NO contiene objetos File (indicaría error de serialización)
     */
    private function validarJsonSinFiles(array $datos, $ruta = ''): void
    {
        foreach ($datos as $key => $valor) {
            $rutaActual = $ruta ? "{$ruta}.{$key}" : $key;
            
            // Si es un array, recursivamente validar
            if (is_array($valor)) {
                $this->validarJsonSinFiles($valor, $rutaActual);
            }
            
            // Si es un objeto (que no sea array), es sospechoso
            if (is_object($valor)) {
                Log::error('[CrearPedidoEditableController] ERROR: Objeto en JSON (File no serializado)', [
                    'ruta' => $rutaActual,
                    'tipo' => get_class($valor)
                ]);
                
                throw new \Exception(
                    "Objeto no serializable en JSON en ruta: {$rutaActual}. " .
                    "Las imágenes deben enviarse por FormData, no por JSON."
                );
            }
            
            // Validación: Si la ruta contiene 'imagenes' y el valor es array vacío []
            // pero esperamos archivos, avisar
            if (strpos($rutaActual, 'imagenes') !== false && 
                is_array($valor) && 
                count($valor) > 0) {
                
                // Validar que cada imagen tiene uid y formdata_key
                foreach ($valor as $idx => $img) {
                    if (is_array($img) && !empty($img)) {
                        if (!isset($img['uid'])) {
                            Log::warning('[CrearPedidoEditableController] Imagen sin UID', [
                                'ruta' => "{$rutaActual}.{$idx}"
                            ]);
                        }
                        if (!isset($img['formdata_key'])) {
                            Log::warning('[CrearPedidoEditableController] Imagen sin formdata_key', [
                                'ruta' => "{$rutaActual}.{$idx}"
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Obtener prendas para autocomplete/datalist
     * GET /asesores/api/prendas/autocomplete
     * 
     * Retorna las prendas activas de la tabla tipos_prenda para rellenar
     * un datalist en el campo de nombre de prenda
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function obtenerPrendasAutocomplete(Request $request): JsonResponse
    {
        try {
            $busqueda = $request->input('q', '');
            
            $query = \App\Models\TipoPrenda::where('activo', true);
            
            // Si hay búsqueda, filtrar por nombre o palabras clave
            if (!empty($busqueda)) {
                $busquedaUpper = strtoupper($busqueda);
                $query->where(function($q) use ($busquedaUpper) {
                    $q->whereRaw('UPPER(nombre) LIKE ?', ["%{$busquedaUpper}%"])
                      ->orWhereRaw('UPPER(codigo) LIKE ?', ["%{$busquedaUpper}%"]);
                });
            }
            
            $prendas = $query->orderBy('nombre', 'asc')
                            ->limit(50)
                            ->get(['id', 'nombre', 'codigo', 'descripcion'])
                            ->map(function($prenda) {
                                return [
                                    'id' => $prenda->id,
                                    'nombre' => $prenda->nombre,
                                    'codigo' => $prenda->codigo,
                                    'descripcion' => $prenda->descripcion
                                ];
                            });
            
            return response()->json([
                'success' => true,
                'prendas' => $prendas
            ]);
            
        } catch (\Exception $e) {
            Log::error('[obtenerPrendasAutocomplete] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener prendas: ' . $e->getMessage()
            ], 500);
        }
    }
}