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
use App\Domain\Pedidos\Services\ProcesoImagenService;
use App\Application\UseCases\Pedidos\CrearPedidoCompleteUseCase;
use App\Application\UseCases\Pedidos\CrearPedidoInput;
use App\Application\UseCases\Pedidos\ValidarPedidoUseCase;
use App\Application\UseCases\Pedidos\ValidarPedidoInput;
use App\Domain\Clientes\Services\ClienteService;

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
        private MapeoImagenesService $mapeoImagenes,
        private ProcesoImagenService $procesoImagenService,
        private CrearPedidoCompleteUseCase $crearPedidoUseCase,
        private ValidarPedidoUseCase $validarPedidoUseCase,
        private ClienteService $clienteService,
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
        Log::info('[CREAR-DESDE-COTIZACION]  INICIANDO CARGA DE PÁGINA', [
            'usuario_id' => Auth::id(),
            'timestamp' => now(),
        ]);
        
        $user = Auth::user();
        Log::info('[CREAR-DESDE-COTIZACION]  Usuario obtenido', [
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
            'tipoCotizacion',  //  Agregar el tipo de cotización
            'prendas' => function($query) {
                $query->with([
                    'fotos', 
                    'telaFotos', 
                    'tallas.genero',  // Cargar tallas con sus géneros
                    'variantes',
                    'logoCotizacionTelasPrenda' => function($q) {  //  Nueva carga para telas de logo
                        // Cargar todas las telas/colores/referencias para esta prenda en cotización de logo
                    }
                ]);
            },
            'logoCotizacion.fotos',
            'logoCotizacion.telasPrendas'  //  Agregar telasPrendas de la cotización de logo
        ])
            ->where('asesor_id', $user->id)
            ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
            ->orderBy('created_at', 'desc')
            ->get();
        $tiempoCotizaciones = round((microtime(true) - $inicioCotizaciones) * 1000, 2);
        Log::info('[CREAR-DESDE-COTIZACION]  Cotizaciones cargadas (CON RELACIONES)', [
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
        Log::info('[CREAR-DESDE-COTIZACION]  Pedidos existentes cargados', [
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
        Log::info('[CREAR-PEDIDO-NUEVO]  INICIANDO CARGA DE PÁGINA', [
            'usuario_id' => Auth::id(),
            'timestamp' => now(),
        ]);
        
        $user = Auth::user();
        Log::info('[CREAR-PEDIDO-NUEVO]  Usuario obtenido', [
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
        Log::debug('[CREAR-PEDIDO-NUEVO]  Cotizaciones (vacías para modo nuevo)', ['cantidad' => 0]);
        
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
        Log::info('[CREAR-PEDIDO-NUEVO]  Pedidos existentes cargados', [
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
        // MODO EDICIÓN: Cargar pedido existente si ?edit=ID
        // ========================================
        $modoEdicion = false;
        $pedidoEditar = null;
        $pedidoEditarId = null;
        $eppsEditar = [];

        $editId = $request->query('edit');
        if ($editId) {
            $editId = (int) $editId;
            $pedidoEditar = PedidoProduccion::with([
                'prendas.tallas',
                'prendas.fotos',
                'prendas.coloresTelas',
                'prendas.procesos',
                'epps.epp',
                'epps.imagenes',
            ])->find($editId);

            if ($pedidoEditar) {
                $modoEdicion = true;
                $pedidoEditarId = $pedidoEditar->id;

                // Usar el campo 'cliente' directo (string) de la tabla pedidos_produccion
                // Si está vacío, buscar via relación cliente_id
                $clienteNombre = $pedidoEditar->getOriginal('cliente') 
                    ?? optional(Cliente::find($pedidoEditar->cliente_id))->nombre 
                    ?? '';
                $pedidoEditar->cliente_nombre_display = $clienteNombre;

                // Preparar prendas con sus relaciones cargadas
                $pedidoEditar->prendas->each(function ($prenda) {
                    $prenda->generosConTallas = $prenda->tallas->groupBy('genero')->map(function ($tallasGenero) {
                        return $tallasGenero->pluck('cantidad', 'talla');
                    });
                    $prenda->cantidadesPorTalla = $prenda->tallas->pluck('cantidad', 'talla');
                    $prenda->telasAgregadas = $prenda->coloresTelas->map(function ($ct) {
                        return [
                            'tela' => $ct->tela ?? '',
                            'nombre_tela' => $ct->tela ?? '',
                            'color' => $ct->color ?? '',
                            'color_nombre' => $ct->color ?? '',
                            'referencia' => $ct->referencia ?? '',
                        ];
                    });
                });

                // Preparar EPPs
                $eppsEditar = $pedidoEditar->epps->map(function ($pedidoEpp) {
                    $nombre = $pedidoEpp->epp?->nombre_completo ?? 'EPP #' . $pedidoEpp->epp_id;
                    return [
                        'epp_id' => $pedidoEpp->epp_id,
                        'nombre_completo' => $nombre,
                        'nombre_epp' => $nombre,
                        'tipo' => 'epp',
                        'cantidad' => $pedidoEpp->cantidad,
                        'observaciones' => $pedidoEpp->observaciones,
                        'imagenes' => $pedidoEpp->imagenes->map(function ($img) {
                            return [
                                'id' => $img->id,
                                'ruta_web' => $img->ruta_web,
                                'principal' => $img->principal,
                            ];
                        })->toArray(),
                    ];
                })->toArray();

                Log::info('[CREAR-PEDIDO-NUEVO] ✏️ MODO EDICIÓN activado', [
                    'pedido_id' => $pedidoEditarId,
                    'prendas' => $pedidoEditar->prendas->count(),
                    'epps' => count($eppsEditar),
                ]);
            } else {
                Log::warning('[CREAR-PEDIDO-NUEVO] ⚠️ Pedido no encontrado para edición', ['edit_id' => $editId]);
            }
        }

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
            'modoEdicion' => $modoEdicion,
            'pedidoEditarId' => $pedidoEditarId,
            'pedido' => $pedidoEditar,
            'epps' => $eppsEditar,
            'estados' => [],
            'areas' => [],
        ]);
        $tiempoView = round((microtime(true) - $inicioView) * 1000, 2);
        
        $tiempoTotalMs = round((microtime(true) - $inicioTotal) * 1000, 2);
        Log::info('[CREAR-PEDIDO-NUEVO] ✨ PÁGINA COMPLETADA', [
            'tiempo_total_ms' => $tiempoTotalMs,
            'tiempo_tallas_ms' => $tiempoTallas,
            'tiempo_pedidos_ms' => $tiempoPedidos,
            'tiempo_clientes_ms' => $tiempoClientes,
            'tiempo_view_ms' => $tiempoView,
            'modo_edicion' => $modoEdicion,
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
     * Obtener items EPP de una cotización (para crear pedido desde cotización)
     *
     * Retorna SOLO datos para renderizar en "Ítems del Pedido":
     * - nombre
     * - cantidad
     * - observaciones
     * - imagenes
     *
     * NO incluye: totales, valor unitario, iva.
     */
    public function obtenerItemsEppCotizacion(Request $request, Cotizacion $cotizacion): JsonResponse
    {
        try {
            if ((int) $cotizacion->asesor_id !== (int) Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado',
                ], 403);
            }

            $items = DB::table('epp_items_cot')
                ->where('cotizacion_id', $cotizacion->id)
                ->orderBy('id')
                ->get(['id', 'nombre', 'cantidad', 'observaciones']);

            $itemIds = $items->pluck('id')->all();
            $imgs = [];
            if (!empty($itemIds)) {
                $imgs = DB::table('epp_img_cot')
                    ->whereIn('epp_item_id', $itemIds)
                    ->orderBy('id')
                    ->get(['epp_item_id', 'ruta'])
                    ->groupBy('epp_item_id')
                    ->map(function ($rows) {
                        return $rows->pluck('ruta')->filter()->values()->all();
                    })
                    ->toArray();
            }

            $itemsUi = $items->map(function ($it) use ($imgs) {
                $rutas = $imgs[$it->id] ?? [];
                $imagenes = array_values(array_filter(array_map(function ($ruta) {
                    if (!$ruta) return null;
                    return url('/storage/' . ltrim($ruta, '/'));
                }, $rutas)));

                $nombre = trim((string) ($it->nombre ?? ''));
                $eppCatalogoId = 0;
                if ($nombre !== '') {
                    $existente = DB::table('epps')
                        ->whereRaw('LOWER(nombre_completo) = ?', [mb_strtolower($nombre)])
                        ->first(['id']);

                    if ($existente) {
                        $eppCatalogoId = (int) $existente->id;
                    } else {
                        $eppCatalogoId = (int) DB::table('epps')->insertGetId([
                            'nombre_completo' => $nombre,
                            'marca' => null,
                            'tipo' => null,
                            'talla' => null,
                            'color' => null,
                            'descripcion' => null,
                            'activo' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                return [
                    'id' => (int) $it->id,
                    'tipo' => 'epp',
                    'nombre' => $it->nombre,
                    'epp_id' => $eppCatalogoId,
                    'cantidad' => (int) ($it->cantidad ?? 1),
                    'observaciones' => $it->observaciones,
                    'imagenes' => $imagenes,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'cotizacion_id' => (int) $cotizacion->id,
                'items' => $itemsUi,
            ]);
        } catch (\Exception $e) {
            Log::error('[CrearPedidoEditableController] Error obtenerItemsEppCotizacion', [
                'cotizacion_id' => $cotizacion->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener items EPP: ' . $e->getMessage(),
            ], 500);
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
     * REFACTORIZADO FASE 2 (Marzo 2026):
     * - Delegado completamente al UseCase ValidarPedidoUseCase
     * - Este Controller solo maneja HTTP (entrada/salida)
     * - UseCase orquesta toda la lógica de validación
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validarPedido(Request $request): JsonResponse
    {
        try {
            // PASO 1: Crear input desde Request
            $input = ValidarPedidoInput::fromRequest($request, Auth::id());

            // PASO 2: Ejecutar UseCase (validación de negocio)
            $output = $this->validarPedidoUseCase->ejecutar($input);

            // PASO 3: Retornar respuesta HTTP
            return response()->json(
                $output->toArray(),
                $output->success ? 200 : 422
            );

        } catch (\Exception $e) {
            Log::error('[CrearPedidoEditableController] Error en validarPedido', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Obtener cliente existente o crear uno nuevo
     * 
     * DELEGADO (FASE 3): Usa ClienteService inyectado
     * 
     * @param string $nombre
     * @return Cliente
     */
    private function obtenerOCrearCliente(string $nombre): Cliente
    {
        return $this->clienteService->obtenerOCrearCliente($nombre);
    }

    /**
     * CREAR PEDIDO CON IMÁGENES - 100% TRANSACCIONAL (REFACTORIZADO FASE 1)
     * POST /asesores/pedidos-editable/crear
     * 
     * FLUJO NUEVO (Marzo 2026):
     * - Delegado completamente al UseCase CrearPedidoCompleteUseCase
     * - Este Controller solo maneja HTTP (entrada/salida)
     * - UseCase orquesta toda la lógica de negocio transaccional
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function crearPedido(Request $request): JsonResponse
    {
        try {
            // PASO 1: Crear input desde Request
            $input = CrearPedidoInput::fromRequest($request, Auth::id());

            // PASO 2: Ejecutar UseCase (100% lógica de negocio en Domain/Application)
            $output = $this->crearPedidoUseCase->ejecutar($input);

            // PASO 3: Retornar respuesta HTTP
            return response()->json(
                $output->toArray(),
                $output->success ? 200 : 500
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[CrearPedidoEditableController] Error en crearPedido', [
                'error' => $e->getMessage(),
            ]);

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

    /**
     * Guardar pedido como BORRADOR (sin número de pedido)
     * 
     * POST /asesores/pedidos-editable/borrador
     * 
     * Similar a crearPedido pero sin generar numero_pedido
     * El borrador se guarda con estado 'Borrador' y puede editarse después
     */
    public function guardarBorrador(Request $request): JsonResponse
    {
        $pedidoId = null;
        $inicioTotal = microtime(true);

        try {
            Log::info('[GUARDAR-BORRADOR]  INICIANDO GUARDADO DE BORRADOR', [
                'has_pedido_json' => !!$request->input('pedido'),
                'archivos_count' => count($request->allFiles()),
                'timestamp' => now(),
            ]);
            
            // Obtener todos los inputs (incluyendo archivos anidados)
            $allInputs = $request->all();
            $archivosRecibidos = [];
            $this->buscarArchivosAnidados($allInputs, '', $archivosRecibidos);
            
            Log::debug('[GUARDAR-BORRADOR] 📤 Archivos en FormData', [
                'total_archivos' => count($archivosRecibidos),
                'archivos' => $archivosRecibidos,
            ]);

            // ====== PASO 1: Decodificar JSON del frontend ======
            $pedidoJSON = $request->input('pedido');
            if (!$pedidoJSON) {
                throw new \Exception('Campo "pedido" JSON requerido');
            }

            $datosFrontend = json_decode($pedidoJSON, true);
            if (!$datosFrontend) {
                throw new \Exception('JSON inválido en campo "pedido"');
            }
            
            $this->validarJsonSinFiles($datosFrontend);
            Log::info('[GUARDAR-BORRADOR]  JSON decodificado');

            // ====== PASO 2: Obtener/crear cliente ======
            $clienteNombre = trim($datosFrontend['cliente'] ?? '');
            $cliente = $this->obtenerOCrearCliente($clienteNombre);

            Log::info('[GUARDAR-BORRADOR]  Cliente obtenido/creado', [
                'cliente_id' => $cliente->id,
                'nombre' => $cliente->nombre,
            ]);

            // ====== PASO 3: Normalizar usando DTO ======
            $dtoPedido = PedidoNormalizadorDTO::fromFrontendJSON(
                $datosFrontend,
                $cliente->id
            );

            Log::info('[GUARDAR-BORRADOR]  Pedido normalizado (DTO)', [
                'cliente_id' => $dtoPedido->cliente_id,
                'prendas' => count($dtoPedido->prendas),
                'epps' => count($dtoPedido->epps),
            ]);

            // ====== PASO 4: Iniciar transacción ======
            DB::beginTransaction();

            // ====== PASO 5: Crear pedido borrador (sin número) ======
            $datosParaServicio = [
                'cliente' => $dtoPedido->cliente,
                'asesora' => $dtoPedido->asesora,
                'forma_de_pago' => $dtoPedido->forma_de_pago,
                'observaciones' => $dtoPedido->observaciones,
                'cliente_id' => $dtoPedido->cliente_id,
                'items' => $dtoPedido->prendas,
                'epps' => $dtoPedido->epps,
            ];

            $pedido = $this->pedidoWebService->crearPedidoBorrador(
                $datosParaServicio,
                Auth::id()
            );

            $pedidoId = $pedido->id;

            Log::info('[GUARDAR-BORRADOR]  Pedido borrador creado', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido ?? 'NULL',
                'estado' => $pedido->estado,
            ]);

            // ====== PASO 6: Crear carpetas ======
            $this->crearCarpetasPedido($pedidoId);

            // ====== PASO 7: Mapear y procesar imágenes ======
            $this->mapeoImagenes->mapearYCrearFotos(
                $dtoPedido,
                $pedidoId,
                $request
            );

            Log::info('[GUARDAR-BORRADOR]  Imágenes mapeadas', [
                'pedido_id' => $pedidoId,
                'imagenes_mapeadas' => count($dtoPedido->imagen_uid_a_ruta),
            ]);

            // ====== PASO 8: Procesar imágenes de EPPs ======
            $eppsCrudos = $datosFrontend['epps'] ?? [];
            if (!empty($eppsCrudos)) {
                $this->procesarImagenesDeEpps($request, $pedidoId, $eppsCrudos);
            }

            // ====== PASO 9: Procesar imágenes de procesos ======
            $procesosData = $datosFrontend['prendas'] ?? [];
            if (!empty($procesosData)) {
                foreach ($procesosData as $prendaIndex => $prendaData) {
                    $procesos = $prendaData['procesos'] ?? [];
                    if (!empty($procesos)) {
                        $this->procesarImagenesDeProcesos($request, $pedidoId, $procesos, $prendaIndex);
                    }
                }
            }

            // ====== Confirmar transacción ======
            DB::commit();

            $tiempoTotal = round((microtime(true) - $inicioTotal) * 1000, 2);

            Log::info('[GUARDAR-BORRADOR]  ✅ BORRADOR GUARDADO EXITOSAMENTE', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido ?? 'NULL (Borrador)',
                'estado' => $pedido->estado,
                'tiempo_total_ms' => $tiempoTotal,
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Borrador guardado exitosamente',
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido ?? null,
                'estado' => $pedido->estado,
                'redirect_url' => route('asesores.pedidos.show', ['pedido' => $pedidoId]),
                'tiempo_ms' => $tiempoTotal,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('[GUARDAR-BORRADOR] ❌ Errores de validación', [
                'pedido_id' => $pedidoId,
                'errores' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[GUARDAR-BORRADOR] ❌ ERROR CRÍTICO', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'trace_resumen' => substr($e->getTraceAsString(), 0, 500),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar borrador: ' . $e->getMessage(),
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile(),
            ], 500);
        }
    }

    /**
     * Actualizar un pedido existente en modo edición
     */
    public function actualizarBorrador($pedidoId, Request $request): JsonResponse
    {
        $inicioTotal = microtime(true);

        try {
            Log::info('[ACTUALIZAR-BORRADOR] INICIANDO ACTUALIZACIÓN', [
                'pedido_id' => $pedidoId,
                'has_pedido_json' => !!$request->input('pedido'),
                'timestamp' => now(),
            ]);

            // Validar que el pedido existe
            $pedido = PedidoProduccion::findOrFail($pedidoId);
            
            // ====== PASO 1: Decodificar JSON del frontend ======
            $pedidoJSON = $request->input('pedido');
            if (!$pedidoJSON) {
                throw new \Exception('Campo "pedido" JSON requerido');
            }

            $datosFrontend = json_decode($pedidoJSON, true);
            if (!$datosFrontend) {
                throw new \Exception('JSON inválido en campo "pedido"');
            }

            // ====== PASO 2: Iniciar transacción ======
            DB::beginTransaction();

            // ====== PASO 3: Actualizar datos básicos del pedido ======
            $pedido->update([
                'cliente' => trim($datosFrontend['cliente'] ?? ''),
                'forma_de_pago' => $datosFrontend['forma_de_pago'] ?? '',
                'observaciones' => $datosFrontend['observaciones'] ?? '',
            ]);

            Log::info('[ACTUALIZAR-BORRADOR] Datos básicos actualizados', [
                'pedido_id' => $pedidoId,
                'cliente' => $pedido->cliente,
            ]);

            // ====== PASO 4: Actualizar EPPs (cantidad, observaciones, e imágenes) ======
            $eppsCrudos = $datosFrontend['epps'] ?? [];
            if (!empty($eppsCrudos)) {
                foreach ($eppsCrudos as $eppIndex => $eppData) {
                    $eppId = $eppData['epp_id'] ?? null;
                    $cantidad = $eppData['cantidad'] ?? 1;
                    $observaciones = $eppData['observaciones'] ?? '';
                    
                    if (!$eppId) continue;
                    
                    // Buscar el registro PedidoEpp existente
                    $pedidoEpp = \App\Models\PedidoEpp::where('pedido_produccion_id', $pedidoId)
                        ->where('epp_id', $eppId)
                        ->first();
                    
                    if ($pedidoEpp) {
                        // ELIMINAR SIEMPRE IMÁGENES ANTIGUAS EN MODO EDICIÓN
                        // Esto asegura que las imágenes se reemplacen completamente
                        // si el usuario las editó (agregó, eliminó, o cambió)
                        $imagenesAntiguas = \App\Models\PedidoEppImagen::where('pedido_epp_id', $pedidoEpp->id)->get();
                        
                        if (count($imagenesAntiguas) > 0) {
                            Log::info('[ACTUALIZAR-BORRADOR] Eliminando imágenes antiguas de EPP (modo edición)', [
                                'pedido_epp_id' => $pedidoEpp->id,
                                'epp_id' => $eppId,
                                'imagenes_a_eliminar' => count($imagenesAntiguas),
                            ]);
                            
                            // Eliminar archivos del storage
                            foreach ($imagenesAntiguas as $imagen) {
                                if ($imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_original)) {
                                    Storage::disk('public')->delete($imagen->ruta_original);
                                }
                                if ($imagen->ruta_web && $imagen->ruta_web !== $imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_web)) {
                                    Storage::disk('public')->delete($imagen->ruta_web);
                                }
                                $imagen->delete();
                            }
                        }
                        
                        // Actualizar cantidad y observaciones
                        $pedidoEpp->update([
                            'cantidad' => $cantidad,
                            'observaciones' => $observaciones,
                        ]);
                        
                        Log::info('[ACTUALIZAR-BORRADOR] EPP actualizado', [
                            'pedido_id' => $pedidoId,
                            'epp_id' => $eppId,
                            'cantidad' => $cantidad,
                            'observaciones' => $observaciones,
                        ]);
                    }
                }
                
                // Procesar imágenes de EPPs (nuevas o copiadas)
                $this->procesarImagenesDeEpps($request, $pedidoId, $eppsCrudos);
            }

            // ====== PASO 5: Procesar imágenes de procesos ======
            $procesosData = $datosFrontend['prendas'] ?? [];
            if (!empty($procesosData)) {
                foreach ($procesosData as $prendaIndex => $prendaData) {
                    $procesos = $prendaData['procesos'] ?? [];
                    if (!empty($procesos)) {
                        $this->procesarImagenesDeProcesos($request, $pedidoId, $procesos, $prendaIndex);
                    }
                }
            }

            // ====== Confirmar transacción ======
            DB::commit();

            $tiempoTotal = round((microtime(true) - $inicioTotal) * 1000, 2);

            Log::info('[ACTUALIZAR-BORRADOR] ✅ PEDIDO ACTUALIZADO EXITOSAMENTE', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'tiempo_total_ms' => $tiempoTotal,
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Pedido actualizado exitosamente',
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'estado' => $pedido->estado,
                'tiempo_ms' => $tiempoTotal,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[ACTUALIZAR-BORRADOR] ❌ ERROR CRÍTICO', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar pedido: ' . $e->getMessage(),
            ], 500);
        }
    }
}