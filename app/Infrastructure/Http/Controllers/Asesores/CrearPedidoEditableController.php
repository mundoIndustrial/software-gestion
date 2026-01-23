<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\UseCases\AgregarItemPedidoUseCase;
use App\Application\Pedidos\UseCases\EliminarItemPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerItemsPedidoUseCase;
use App\Domain\PedidoProduccion\Services\TransformadorCotizacionService;
use App\Domain\PedidoProduccion\Services\FormDataProcessorService;
use App\Domain\PedidoProduccion\Services\ItemValidationService;
use App\Domain\PedidoProduccion\Services\ItemTransformerService;
use App\Domain\PedidoProduccion\Services\VariacionesProcessorService;
use App\Domain\PedidoProduccion\Services\ImagenProcessorService;
use App\Domain\PedidoProduccion\Services\EppProcessorService;
use App\Domain\PedidoProduccion\Services\PedidoCreationService;
use App\Domain\PedidoProduccion\Services\ImagenMapperService;
use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Services\PedidoEppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CrearPedidoEditableController - REFACTORIZADO CON USE CASES
 * 
 * Responsabilidad:
 * - Recibir requests HTTP del flujo de creación editable
 * - Usar Use Cases de DDD para operaciones
 * - Formatear respuestas JSON
 * 
 * Patrón: Use Cases (DDD) + Dependency Injection
 * SRP: Solo HTTP, delegando lógica a Use Cases
 */
class CrearPedidoEditableController extends Controller
{
    public function __construct(
        private AgregarItemPedidoUseCase $agregarItemUseCase,
        private EliminarItemPedidoUseCase $eliminarItemUseCase,
        private ObtenerItemsPedidoUseCase $obtenerItemsUseCase,
        private TransformadorCotizacionService $transformador,
        private FormDataProcessorService $formDataProcessor,
        private ItemValidationService $itemValidator,
        private ItemTransformerService $itemTransformer,
        private VariacionesProcessorService $variacionesProcessor,
        private ImagenProcessorService $imagenProcessor,
        private EppProcessorService $eppProcessor,
        private PedidoCreationService $pedidoCreationService,
        private ImagenMapperService $imagenMapper,
        private PedidoEppService $eppService,
    ) {}

    public function index(?string $tipoInicial = null): View
    {
        $cotizaciones = Cotizacion::with(['cliente', 'asesor', 'prendasCotizaciones'])
            ->where('estado', 'aprobada')
            ->get();

        $cotizacionesTransformadas = $this->transformador->transformarCotizacionesParaFrontend($cotizaciones);

        return view('asesores.pedidos.crear-desde-cotizacion-editable', [
            'tipoInicial' => $tipoInicial,
            'cotizaciones' => $cotizaciones,
            'cotizacionesData' => $cotizacionesTransformadas,
        ]);
    }

    public function agregarItem(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tipo' => 'required|in:cotizacion,nuevo',
                'prenda' => 'required|array',
                'origen' => 'required|in:bodega,confeccion',
                'procesos' => 'array',
                'es_proceso' => 'boolean',
                'cotizacion_id' => 'nullable|integer|exists:cotizaciones,id',
                'tallas' => 'nullable|array',
                'variaciones' => 'nullable|array',
                'imagenes' => 'nullable|array',
            ]);

            // Usar Use Case para agregar item
            $result = $this->agregarItemUseCase->ejecutar($validated);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar ítem: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function eliminarItem(Request $request): JsonResponse
    {
        try {
            $index = $request->integer('index');
            
            // Usar Use Case para eliminar item
            $result = $this->eliminarItemUseCase->ejecutar($index);

            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar ítem: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function obtenerItems(): JsonResponse
    {
        try {
            // Usar Use Case para obtener items
            $result = $this->obtenerItemsUseCase->ejecutar();

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerItems:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener items: ' . $e->getMessage(),
                'items' => [],
                'count' => 0,
                'tieneItems' => false,
            ], 500);
        }
    }

    public function validarPedido(Request $request): JsonResponse
    {
        \Log::info(' [validarPedido] Método llamado');
        
        return response()->json([
            'valid' => true,
            'message' => 'Pedido válido',
        ]);
    }

    public function crearPedido(Request $request): JsonResponse
    {
        try {
            // Obtener items del request
            $itemsDesdeItems = $request->input('items', []);
            $itemsDesdePrendas = $request->input('prendas', []);
            
            $items = [];
            if (!empty($itemsDesdeItems)) {
                $items = array_merge($items, is_array($itemsDesdeItems) ? array_values($itemsDesdeItems) : []);
            }
            if (!empty($itemsDesdePrendas)) {
                $items = array_merge($items, is_array($itemsDesdePrendas) ? array_values($itemsDesdePrendas) : []);
            }
            
            // Validar items usando servicio DDD
            $erroresValidacion = $this->itemValidator->validarHayItems($items);
            if (!empty($erroresValidacion)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido tiene errores',
                    'errores' => $erroresValidacion,
                ], 422);
            }

            $erroresValidacion = $this->itemValidator->validarTodosLosItems($items);
            if (!empty($erroresValidacion)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido tiene errores',
                    'errores' => $erroresValidacion,
                ], 422);
            }

            // Validar datos básicos
            $validated = $request->validate([
                'cliente' => 'required|string',
                'asesora' => 'required|string',
                'forma_de_pago' => 'nullable|string',
            ]);
            
            $validated['items'] = $items;

            // Obtener usuario autenticado
            $asesora = auth()->user();
            if (!$asesora) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            // Crear pedido usando servicio DDD
            $pedido = $this->pedidoCreationService->crearPedido(
                $validated['cliente'],
                $asesora,
                $validated['forma_de_pago']
            );

            // Procesar prendas y EPPs
            $prendasParaGuardar = [];
            $eppsParaGuardar = [];
            $cantidadTotal = 0;
            
            foreach ($validated['items'] as $itemIndex => $item) {
                $tipo = $item['tipo'] ?? 'cotizacion';
                
                // Procesar EPP
                if ($tipo === 'epp') {
                    $eppData = $this->eppProcessor->construirEppConImagenes($request, $itemIndex, $item, $pedido->id);
                    $eppsParaGuardar[] = $eppData;
                    $cantidadTotal += (int)($item['cantidad'] ?? 0);
                    continue;
                }
                
                // Procesar Prenda
                // Reconstruir procesos usando servicio DDD
                $procesosReconstruidos = $this->formDataProcessor->reconstruirProcesos($request, $itemIndex);
                
                // Extraer imágenes de procesos usando servicio DDD (desde FormData)
                $procesosReconstruidos = $this->formDataProcessor->extraerImagenesProcesos($request, $itemIndex, $procesosReconstruidos);
                
                // Extraer imágenes de prenda usando servicio DDD (desde FormData)
                $fotosFiltered = $this->formDataProcessor->extraerImagenesPrenda($request, $itemIndex);
                
                // Si no hay imágenes en FormData, mapear desde JSON del item
                if (empty($fotosFiltered)) {
                    $fotosFiltered = $this->imagenMapper->mapearImagenesPrenda($item);
                }
                
                // Extraer imágenes de telas usando servicio DDD (desde FormData)
                $telasConImagenes = $this->formDataProcessor->extraerImagenesTelas($request, $itemIndex, $item['telas'] ?? []);
                
                // Si no hay imágenes en FormData, mapear desde JSON del item
                if (empty($telasConImagenes) || !$this->tieneImagenesEnTelas($telasConImagenes)) {
                    $telasConImagenes = $this->imagenMapper->mapearImagenesTelas($item['telas'] ?? []);
                }
                
                // Procesar cantidad_talla usando servicio DDD (parsea JSON si es necesario)
                $cantidadTallaProcessada = $this->itemTransformer->procesarCantidadTalla($item['cantidad_talla'] ?? []);
                
                // Copiar tallas a procesos usando servicio DDD
                $procesosReconstruidos = $this->itemTransformer->copiarTallasAProcesos(
                    $procesosReconstruidos,
                    $cantidadTallaProcessada
                );
                
                // Procesar variaciones (manga, broche, color, tela) usando servicio DDD
                $variacionesIds = $this->variacionesProcessor->procesarVariaciones($item);
                
                // Extraer variaciones desde JSON si existen
                $variacionesJSON = [];
                if (!empty($item['variaciones'])) {
                    if (is_string($item['variaciones'])) {
                        $variacionesJSON = json_decode($item['variaciones'], true) ?? [];
                    } else {
                        $variacionesJSON = $item['variaciones'];
                    }
                }
                
                // Extraer manga, broche y observaciones desde variaciones JSON
                $manga = $variacionesJSON['manga'] ?? '';
                $mangaObs = $variacionesJSON['obs_manga'] ?? $request->input("items.{$itemIndex}.obs_manga") ?? $item['obs_manga'] ?? '';
                $broche = $variacionesJSON['broche'] ?? '';
                $brocheObs = $variacionesJSON['obs_broche'] ?? $request->input("items.{$itemIndex}.obs_broche") ?? $item['obs_broche'] ?? '';
                $bolsillosObs = $variacionesJSON['obs_bolsillos'] ?? $request->input("items.{$itemIndex}.obs_bolsillos") ?? $item['obs_bolsillos'] ?? '';
                $reflectivoObs = $variacionesJSON['obs_reflectivo'] ?? $request->input("items.{$itemIndex}.obs_reflectivo") ?? $item['obs_reflectivo'] ?? '';
                $tieneBolsillos = $variacionesJSON['tiene_bolsillos'] ?? $request->input("items.{$itemIndex}.tiene_bolsillos") ?? $item['tiene_bolsillos'] ?? false;
                $tieneReflectivo = $variacionesJSON['tiene_reflectivo'] ?? $request->input("items.{$itemIndex}.tiene_reflectivo") ?? $item['tiene_reflectivo'] ?? false;
                
                // Pasar variaciones al item para que se incluyan en prendaData
                $item['manga'] = $manga;
                $item['obs_manga'] = $mangaObs;
                $item['broche'] = $broche;
                $item['obs_broche'] = $brocheObs;
                $item['obs_bolsillos'] = $bolsillosObs;
                $item['obs_reflectivo'] = $reflectivoObs;
                $item['tiene_bolsillos'] = (bool)$tieneBolsillos;
                $item['tiene_reflectivo'] = (bool)$tieneReflectivo;
                
                // Transformar item a formato esperado por PedidoPrendaService usando servicio DDD
                $prendaData = $this->itemTransformer->transformarItemAPrenda(
                    $item,
                    $fotosFiltered,
                    $procesosReconstruidos,
                    $telasConImagenes,
                    $variacionesIds['tipo_manga_id'],
                    $variacionesIds['tipo_broche_boton_id']
                );
                
                // Agregar IDs de variaciones a prendaData
                $prendaData['color_id'] = $variacionesIds['color_id'];
                $prendaData['tela_id'] = $variacionesIds['tela_id'];
                $prendaData['tipo_manga_id'] = $variacionesIds['tipo_manga_id'];
                $prendaData['tipo_broche_boton_id'] = $variacionesIds['tipo_broche_boton_id'];
                
                // Agregar observaciones a prendaData para que el servicio las use
                $prendaData['obs_manga'] = $mangaObs;
                $prendaData['obs_broche'] = $brocheObs;
                $prendaData['obs_bolsillos'] = $bolsillosObs;
                $prendaData['obs_reflectivo'] = $reflectivoObs;
                $prendaData['tiene_bolsillos'] = (bool)$tieneBolsillos;
                $prendaData['tiene_reflectivo'] = (bool)$tieneReflectivo;
                
                // Calcular cantidad total
                $tipo = $item['tipo'] ?? 'cotizacion';
                if ($tipo === 'nuevo' || $tipo === 'prenda_nueva') {
                    // Usar cantidad_talla ya procesada
                    $cantidadItem = $this->itemTransformer->calcularCantidadDeCantidadTalla($cantidadTallaProcessada);
                } else {
                    $cantidadItem = $this->itemTransformer->calcularCantidadDeTallas($item['tallas'] ?? []);
                }
                $cantidadTotal += $cantidadItem;
                
                $prendasParaGuardar[] = $prendaData;
            }
            
            //  LOG DE VERIFICACIÓN ANTES DE GUARDAR
            \Log::info(' [CrearPedidoEditableController] Prendas listas para guardar', [
                'cantidad_prendas' => count($prendasParaGuardar),
                'prendas_estructura' => array_map(function($p) {
                    return [
                        'nombre' => $p['nombre_producto'] ?? 'SIN NOMBRE',
                        'tiene_fotos' => !empty($p['fotos']) ? count($p['fotos']) : 0,
                        'tiene_telas' => !empty($p['telas']) ? count($p['telas']) : 0,
                        'tiene_procesos' => !empty($p['procesos']) ? count($p['procesos']) : 0,
                        'color_id' => $p['color_id'] ?? 'NULL',
                        'tela_id' => $p['tela_id'] ?? 'NULL',
                        'tipo_manga_id' => $p['tipo_manga_id'] ?? 'NULL',
                        'tipo_broche_boton_id' => $p['tipo_broche_boton_id'] ?? 'NULL',
                        'cantidad_talla' => $p['cantidad_talla'] ?? 'NULL',
                    ];
                }, $prendasParaGuardar),
            ]);
            
            // Guardar todas las prendas usando el servicio
            $this->pedidoPrendaService->guardarPrendasEnPedido($pedido, $prendasParaGuardar);
            
            //  GUARDAR EPPS SI LOS HAY
            if (!empty($eppsParaGuardar)) {
                \Log::info(' Guardando EPPs del pedido:', [
                    'cantidad_epps' => count($eppsParaGuardar),
                    'epps' => array_map(function($e) {
                        return [
                            'nombre' => $e['nombre'],
                            'cantidad' => $e['cantidad'],
                        ];
                    }, $eppsParaGuardar),
                ]);
                
                try {
                    $this->eppService->guardarEppsDelPedido($pedido, $eppsParaGuardar);
                    \Log::info(' EPPs guardados exitosamente para pedido:', ['pedido_id' => $pedido->id]);
                } catch (\Exception $e) {
                    \Log::error(' Error guardando EPPs:', [
                        'error' => $e->getMessage(),
                        'pedido_id' => $pedido->id,
                    ]);
                    // No lanzar error, solo loguear (los EPPs no bloquean la creación del pedido)
                }
            }

            // Actualizar cantidad total del pedido
            $pedido->update(['cantidad_total' => $cantidadTotal]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado correctamente',
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);
        } catch (\Exception $e) {
            \Log::error(' Error en crearPedido:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage(),
            ], 422);
        }
    }


    /**
     * Subir imágenes de prenda via FormData
     * POST /asesores/pedidos-editable/subir-imagenes
     */
    public function subirImagenesPrenda(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'imagenes' => 'required|array',
                'imagenes.*' => 'required|file|image|max:10240',
                'numero_pedido' => 'required|string',
            ]);

            $numeroPedido = $request->input('numero_pedido');
            $rutasGuardadas = [];

            foreach ($request->file('imagenes', []) as $index => $archivo) {
                if (!$archivo->isValid()) {
                    \Log::warning(' Archivo inválido', [
                        'numero_pedido' => $numeroPedido,
                        'index' => $index,
                    ]);
                    continue;
                }

                try {
                    $ruta = $this->imagenProcessor->procesarYGuardarImagen($archivo, $numeroPedido, $index);
                    if ($ruta) {
                        $rutasGuardadas[] = json_encode($ruta);
                    }
                } catch (\Exception $e) {
                    \Log::error(' Error procesando imagen', [
                        'numero_pedido' => $numeroPedido,
                        'index' => $index,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Imágenes subidas correctamente',
                'rutas' => $rutasGuardadas,
            ]);
        } catch (\Exception $e) {
            \Log::error(' Error en subirImagenesPrenda', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al subir imágenes: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verificar si hay imágenes en las telas
     */
    private function tieneImagenesEnTelas(array $telasConImagenes): bool
    {
        foreach ($telasConImagenes as $tela) {
            if (!empty($tela['fotos'])) {
                return true;
            }
        }
        return false;
    }
}
