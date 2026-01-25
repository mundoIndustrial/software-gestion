<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\Cotizacion;
use App\Models\Talla;
use App\Http\Requests\CrearPedidoCompletoRequest;
use App\Domain\Pedidos\Services\PedidoWebService;
use Illuminate\Support\Facades\Auth;

/**
 * CrearPedidoEditableController
 * 
 * MASTER Controller para CREACIÓN DE PEDIDOS
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
        private PedidoWebService $pedidoWebService
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
        $user = Auth::user();
        
        // ========================================
        // DATOS COMPARTIDOS (SIEMPRE)
        // ========================================
        
        // Obtener las tallas disponibles
        $tallas = Talla::all();
        
        // Formas de pago disponibles (ValueObject)
        $formasPago = [
            'Contado',
            'Crédito 15 días',
            'Crédito 30 días',
            'Crédito 60 días',
            'Transferencia',
            'Cheque'
        ];
        
        // Técnicas disponibles (se definen en JavaScript frontend, pasamos array simple)
        $tecnicas = [
            'Bordado',
            'Estampado',
            'DTF',
            'Sublimado',
            'Tejido',
            'Serigrafía'
        ];
        
        // ========================================
        // DATO CRÍTICO: COTIZACIONES DEL USUARIO (IMPORTANTE AQUÍ)
        // ========================================
        
        // Cargar cotizaciones aprobadas para crear pedidos
        $cotizaciones = Cotizacion::with([
            'cliente',
            'prendas' => function($query) {
                $query->with(['fotos', 'telaFotos', 'tallas', 'variantes']);
            },
            'logoCotizacion.fotos',
            'reflectivoCotizacion.fotos'
        ])
            ->where('asesor_id', $user->id)
            ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // ========================================
        // DATO CRÍTICO: PEDIDOS EXISTENTES
        // ========================================
        
        // Obtener pedidos disponibles para edición
        $pedidos = PedidoProduccion::where('asesor_id', $user->id)
            ->where('estado', '!=', 'completado')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // ========================================
        // DATO CRÍTICO: CLIENTES
        // ========================================
        
        // Obtener clientes para dropdown manual si se crea sin cotización
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        
        // ========================================
        // RETORNAR VIEW CON TODOS LOS DATOS
        // ========================================
        
        return view('asesores.pedidos.crear-pedido-nuevo', [
            'cotizaciones' => $cotizaciones,
            'pedidos' => $pedidos,
            'clientes' => $clientes,
            'tallas' => $tallas,
            'tecnicas' => $tecnicas,
            'formasPago' => $formasPago,
            'modoEdicion' => false
        ]);
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
        $user = Auth::user();
        
        // ========================================
        // DATOS COMPARTIDOS (SIEMPRE)
        // ========================================
        
        // Obtener las tallas disponibles
        $tallas = Talla::all();
        
        // Formas de pago disponibles (ValueObject)
        $formasPago = [
            'Contado',
            'Crédito 15 días',
            'Crédito 30 días',
            'Crédito 60 días',
            'Transferencia',
            'Cheque'
        ];
        
        // Técnicas disponibles (se definen en JavaScript frontend, pasamos array simple)
        $tecnicas = [
            'Bordado',
            'Estampado',
            'DTF',
            'Sublimado',
            'Tejido',
            'Serigrafía'
        ];
        
        // ========================================
        // COTIZACIONES: Vacía para crear nuevo
        // ========================================
        // NO cargamos cotizaciones, el usuario crea pedido desde cero con cliente manual
        $cotizaciones = collect([]);
        
        // ========================================
        // DATO CRÍTICO: PEDIDOS EXISTENTES
        // ========================================
        
        // Obtener pedidos disponibles para edición
        $pedidos = PedidoProduccion::where('asesor_id', $user->id)
            ->where('estado', '!=', 'completado')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // ========================================
        // DATO CRÍTICO: CLIENTES (IMPORTANTE AQUÍ)
        // ========================================
        
        // Obtener todos los clientes para dropdown manual (ESENCIAL para "Pedido Nuevo")
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        
        // ========================================
        // RETORNAR VIEW CON TODOS LOS DATOS
        // ========================================
        
        return view('asesores.pedidos.crear-pedido-nuevo', [
            'cotizaciones' => $cotizaciones,
            'pedidos' => $pedidos,
            'clientes' => $clientes,
            'tallas' => $tallas,
            'tecnicas' => $tecnicas,
            'formasPago' => $formasPago,
            'modoEdicion' => false
        ]);
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
     * @param Request $request
     * @return JsonResponse
     */
    public function validarPedido(Request $request): JsonResponse
    {
        try {
            \Log::info('[CrearPedidoEditableController] validarPedido - Datos recibidos', [
                'cliente' => $request->input('cliente'),
                'items_count' => count($request->input('items', [])),
                'all_input' => $request->all()
            ]);

            // Validación inicial
            $validated = $request->validate([
                'cliente' => 'required|string',  // Aceptar nombre del cliente
                'descripcion' => 'nullable|string|max:1000',
                'items' => 'required|array|min:1',
                'items.*.nombre_prenda' => 'required|string',
                'items.*.cantidad_talla' => 'nullable|array', // Puede ser array vacío o tener items
            ]);

            \Log::info('[CrearPedidoEditableController] Validación pasada', $validated);

            // Obtener o crear el cliente
            $clienteNombre = trim($request->input('cliente'));
            $cliente = $this->obtenerOCrearCliente($clienteNombre);

            return response()->json([
                'success' => true,
                'message' => 'Validación exitosa',
                'cliente_id' => $cliente->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('[CrearPedidoEditableController] Validación fallida', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('[CrearPedidoEditableController] Error general', [
                'error' => $e->getMessage(),
                'input' => $request->all()
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
     * Crear el pedido completo con todas sus prendas e imágenes
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function crearPedido(Request $request): JsonResponse
    {
        try {
            \Log::info('[CrearPedidoEditableController] crearPedido - Inicio', [
                'has_pedido_json' => !!$request->input('pedido'),
                'all_files' => count($request->allFiles()),
            ]);

            // 1. Procesar archivos y decodificar JSON
            $validated = $this->procesarArchivosYValidar($request);

            // 2. Obtener o crear cliente
            $clienteNombre = trim($validated['cliente']);
            $cliente = $this->obtenerOCrearCliente($clienteNombre);

            $validated['cliente_id'] = $cliente->id;

            \Log::info('[CrearPedidoEditableController] Cliente obtenido', [
                'cliente_id' => $cliente->id,
                'nombre' => $cliente->nombre,
            ]);

            // 3. Crear pedido completo usando PedidoWebService
            $pedido = $this->pedidoWebService->crearPedidoCompleto(
                $validated,
                \Illuminate\Support\Facades\Auth::id()
            );

            \Log::info('[CrearPedidoEditableController] ✅ Pedido creado exitosamente', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cantidad_prendas' => $pedido->prendas()->count(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente_id' => $cliente->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('[CrearPedidoEditableController] Error al crear pedido', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage(),
            ], 500);
        }
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
        // ✅ PASO 1: Decodificar metadata JSON
        $pedidoJSON = $request->input('pedido');
        if (!$pedidoJSON) {
            throw new \Exception('Campo "pedido" JSON requerido en FormData');
        }

        $pedido = json_decode($pedidoJSON, true);
        if (!$pedido) {
            throw new \Exception('JSON inválido en campo "pedido"');
        }

        \Log::info('[CrearPedidoEditableController] ✅ Metadata decodificada', [
            'cliente' => $pedido['cliente'],
            'items_count' => count($pedido['items'] ?? []),
            'all_files' => array_keys($request->allFiles()),
        ]);

        // ✅ PASO 2: Procesar archivos e inyectarlos en pedido
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

        \Log::info('[CrearPedidoEditableController] ✅ Archivos procesados', [
            'items_with_images' => count(array_filter($pedido['items'], fn($i) => isset($i['imagenes']))),
            'archivos_totales' => count($request->allFiles()),
        ]);

        // ✅ VERIFICACIÓN CRÍTICA: Verificar estructura de items
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

        // ✅ PASO 3: Retornar pedido con archivos inyectados
        return [
            'cliente' => $pedido['cliente'],
            'asesora' => $pedido['asesora'],
            'forma_de_pago' => $pedido['forma_de_pago'],
            'descripcion' => $pedido['descripcion'] ?? '',
            'items' => $pedido['items'],
        ];
    }

    /**
     * Guardar imagen y retornar ruta
     * 
     * @param \Illuminate\Http\UploadedFile $archivo
     * @param string $carpeta
     * @return string
     */
    private function guardarImagen($archivo, string $carpeta): string
    {
        // Generar nombre único
        $nombreArchivo = time() . '_' . uniqid() . '.' . $archivo->getClientOriginalExtension();
        
        // Guardar en storage/app/public/{carpeta}
        $ruta = $archivo->storeAs("{$carpeta}/" . date('Y/m'), $nombreArchivo, 'public');
        
        return $ruta;
    }

    /**
     * Subir imágenes de prenda
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function subirImagenesPrenda(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'imagenes' => 'required|array|min:1',
                'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
            ]);

            $uploadedPaths = [];

            foreach ($request->file('imagenes') as $imagen) {
                $path = $imagen->store('prendas/temp', 'public');
                $uploadedPaths[] = [
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Imágenes subidas correctamente',
                'imagenes' => $uploadedPaths,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir imágenes: ' . $e->getMessage(),
            ], 422);
        }
    }
}
