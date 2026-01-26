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
        private PedidoWebService $pedidoWebService,
        private ImageUploadService $imageUploadService
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
     * CREAR PEDIDO CON IMÁGENES - 100% TRANSACCIONAL
     * POST /asesores/pedidos-editable/crear
     * 
     * TODO O NADA: Si falla algo, rollback completo (DB + archivos)
     * NO carpetas temporales
     * NO relocalización
     * Pedido + imágenes en una sola operación atómica
     * 
     * Soporta DOS estructuras:
     * 
     * NUEVA ESTRUCTURA (separada):
     * - pedido: JSON con estructura {cliente, asesora, forma_de_pago, prendas[], epps[]}
     * - prendas[i][imagenes][j]: archivos de imágenes de prendas
     * - prendas[i][telas][k][imagenes][l]: archivos de imágenes de telas
     * - prendas[i][procesos][m][imagenes][n]: archivos de imágenes de procesos
     * - epps[i][imagenes][j]: archivos de imágenes de epps (convertirán a WebP)
     * 
     * ESTRUCTURA ANTIGUA (compatibilidad):
     * - pedido: JSON con estructura {cliente, asesora, forma_de_pago, items[]}
     * - prendas[i][imagenes][j]: archivos de imágenes
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function crearPedido(Request $request): JsonResponse
    {
        $pedidoId = null;
        
        try {
            Log::info('[CrearPedidoEditableController] Iniciando creación transaccional', [
                'has_pedido_json' => !!$request->input('pedido'),
                'archivos_count' => count($request->allFiles()),
            ]);

            // PASO 1: Decodificar JSON metadata
            $pedidoJSON = $request->input('pedido');
            if (!$pedidoJSON) {
                throw new \Exception('Campo "pedido" JSON requerido en FormData');
            }

            $validated = json_decode($pedidoJSON, true);
            if (!$validated) {
                throw new \Exception('JSON inválido en campo "pedido"');
            }

            // Detectar estructura: nueva (prendas/epps) o antigua (items)
            $esEstructuraNueva = isset($validated['prendas']) || isset($validated['epps']);
            $esEstructuraAntiga = isset($validated['items']);

            Log::info('[CrearPedidoEditableController] Estructura detectada', [
                'nueva' => $esEstructuraNueva ? 'SÍ (prendas/epps)' : 'NO',
                'antigua' => $esEstructuraAntiga ? 'SÍ (items)' : 'NO',
            ]);

            // PASO 2: Normalizar para compatibilidad con PedidoWebService
            // Si es estructura nueva, convertir a formato esperado por servicio
            if ($esEstructuraNueva && !$esEstructuraAntiga) {
                $validated['items'] = $validated['prendas'] ?? [];
                $validated['epps'] = $validated['epps'] ?? [];
            }

            // PASO 3: Obtener o crear cliente
            $clienteNombre = trim($validated['cliente']);
            $cliente = $this->obtenerOCrearCliente($clienteNombre);
            $validated['cliente_id'] = $cliente->id;

            // ========================================
            // INICIO TRANSACCIÓN ATÓMICA
            // ========================================
            DB::beginTransaction();

            // PASO 4: Crear pedido PRIMERO (para obtener pedido_id)
            // El servicio solo procesa prendas (items), los epps se procesan después
            $pedido = $this->pedidoWebService->crearPedidoCompleto(
                $validated,
                Auth::id()
            );
            
            $pedidoId = $pedido->id;

            Log::info('[CrearPedidoEditableController] Pedido creado en transacción', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'prendas_count' => count($validated['items'] ?? []),
            ]);

            // PASO 5: Procesar imágenes de prendas
            $this->procesarYAsignarImagenes($request, $pedidoId, $validated['items'] ?? []);

            Log::info('[CrearPedidoEditableController] Imágenes de prendas procesadas', [
                'pedido_id' => $pedidoId,
            ]);

            // PASO 6: Procesar EPPs si existen en estructura nueva
            if ($esEstructuraNueva && !empty($validated['epps'])) {
                $this->procesarYAsignarEpps($request, $pedidoId, $validated['epps']);

                Log::info('[CrearPedidoEditableController] EPPs procesados', [
                    'pedido_id' => $pedidoId,
                    'epps_count' => count($validated['epps']),
                ]);
            }

            // PASO 7: Actualizar cantidad_total del pedido
            // cantidad_total = suma de cantidades de prendas + suma de cantidades de EPPs
            $cantidadPrendas = $this->calcularCantidadTotalPrendas($pedidoId);
            $cantidadEpps = $this->calcularCantidadTotalEpps($pedidoId);
            $cantidadTotal = $cantidadPrendas + $cantidadEpps;

            $pedido->update(['cantidad_total' => $cantidadTotal]);

            Log::info('[CrearPedidoEditableController] 📊 Cantidad total actualizada', [
                'pedido_id' => $pedidoId,
                'cantidad_prendas' => $cantidadPrendas,
                'cantidad_epps' => $cantidadEpps,
                'cantidad_total' => $cantidadTotal,
            ]);

            // PASO 8: Todo OK → COMMIT
            DB::commit();

            Log::info('[CrearPedidoEditableController] TRANSACCIÓN EXITOSA', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente_id' => $cliente->id,
            ]);

        } catch (\Exception $e) {
            // ROLLBACK DB
            DB::rollBack();

            Log::error('[CrearPedidoEditableController]  ERROR - Iniciando rollback', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);

            // LIMPIAR ARCHIVOS: Borrar carpeta completa del pedido si se creó
            if ($pedidoId) {
                try {
                    $carpetaPedido = "pedidos/{$pedidoId}";
                    if (Storage::disk('public')->exists($carpetaPedido)) {
                        Storage::disk('public')->deleteDirectory($carpetaPedido);
                        Log::info('[CrearPedidoEditableController] 🗑️ Carpeta eliminada', [
                            'carpeta' => $carpetaPedido,
                        ]);
                    }
                } catch (\Exception $cleanupError) {
                    Log::error('[CrearPedidoEditableController] Error limpiando archivos', [
                        'pedido_id' => $pedidoId,
                        'error' => $cleanupError->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage(),
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
                $telasRelacion = $prenda->coloresTelas;
                
                foreach ($item['telas'] as $telaIdx => $tela) {
                    if (!isset($telasRelacion[$telaIdx])) {
                        continue;
                    }

                    $imgIdx = 0;
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
                            'prenda_pedido_colores_telas_id' => $telasRelacion[$telaIdx]->id,
                            'ruta_webp' => $resultado['webp'],
                            'orden' => $imgIdx + 1,
                        ]);

                        Log::debug('[CrearPedidoEditableController] 📸 Imagen tela guardada', [
                            'tela_relacion_id' => $telasRelacion[$telaIdx]->id,
                            'webp' => $resultado['webp'],
                        ]);

                        $imgIdx++;
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
                    //  NOTA: La columna se llama ruta_web (no ruta_webp)
                    // y principal (no es_principal)
                    PedidoEppImagen::create([
                        'pedido_epp_id' => $pedidoEpp->id,
                        'ruta_web' => $resultado['webp'],  // Convertida a WebP
                        'orden' => $imgIdx + 1,
                        'principal' => $imgIdx === 0 ? 1 : 0,  // Primera imagen es principal
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
                $telasRelacion = $prenda->coloresTelas;
                
                if (isset($telasRelacion[$telaIdx])) {
                    \App\Models\PrendaFotoTelaPedido::create([
                        'prenda_pedido_colores_telas_id' => $telasRelacion[$telaIdx]->id,
                        'ruta_original' => $rutaOriginalNew,
                        'ruta_webp' => $rutaWebpNew,
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
                ->join('procesos_prenda_detalle as ppd', 'pppt.proceso_prenda_detalle_id', '=', 'ppd.id')
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
    }}