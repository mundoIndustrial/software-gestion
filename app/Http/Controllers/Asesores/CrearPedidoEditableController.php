<?php

namespace App\Http\Controllers\Asesores;

use App\Application\DTOs\ItemPedidoDTO;
use App\Application\Services\PedidoPrendaService;
use App\Application\Services\ColorGeneroMangaBrocheService;
use App\Domain\PedidoProduccion\Services\GestionItemsPedidoService;
use App\Domain\PedidoProduccion\Services\TransformadorCotizacionService;
use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrearPedidoEditableController extends Controller
{
    public function __construct(
        private GestionItemsPedidoService $gestionItems,
        private TransformadorCotizacionService $transformador,
        private PedidoPrendaService $pedidoPrendaService,
        private ColorGeneroMangaBrocheService $colorGeneroService,
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

            $itemDTO = ItemPedidoDTO::fromArray($validated);
            $this->gestionItems->agregarItem($itemDTO);

            return response()->json([
                'success' => true,
                'message' => 'Ítem agregado correctamente',
                'items' => $this->gestionItems->obtenerItemsArray(),
                'count' => $this->gestionItems->contar(),
            ]);
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
            
            if ($index < 0 || $index >= $this->gestionItems->contar()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Índice de ítem inválido',
                ], 422);
            }

            $this->gestionItems->eliminarItem($index);

            return response()->json([
                'success' => true,
                'message' => 'Ítem eliminado correctamente',
                'items' => $this->gestionItems->obtenerItemsArray(),
                'count' => $this->gestionItems->contar(),
            ]);
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
            return response()->json([
                'items' => $this->gestionItems->obtenerItemsArray(),
                'count' => $this->gestionItems->contar(),
                'tieneItems' => $this->gestionItems->tieneItems(),
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error en obtenerItems:', [
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
        $items = $request->input('items', []);
        
        $errores = [];
        
        // Validar que haya al menos un ítem
        if (empty($items)) {
            $errores[] = 'Debe agregar al menos un ítem al pedido';
            return response()->json([
                'valid' => false,
                'errores' => $errores,
            ], 422);
        }
        
        // Validar cada ítem
        foreach ($items as $index => $item) {
            $itemNum = $index + 1;
            
            if (empty($item['prenda'])) {
                $errores[] = "Ítem {$itemNum}: Prenda no especificada";
            }
            
            if (empty($item['tallas']) || !is_array($item['tallas']) || count($item['tallas']) === 0) {
                $errores[] = "Ítem {$itemNum}: Debe seleccionar al menos una talla";
            }
        }
        
        if (!empty($errores)) {
            return response()->json([
                'valid' => false,
                'errores' => $errores,
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Pedido válido',
        ]);
    }

    public function crearPedido(Request $request): JsonResponse
    {
        try {
            // Obtener items del request
            $items = $request->input('items', []);
            
            // Validar que haya al menos un ítem
            if (empty($items)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido tiene errores',
                    'errores' => ['Debe agregar al menos un ítem al pedido'],
                ], 422);
            }
            
            // Validar cada ítem
            $errores = [];
            foreach ($items as $index => $item) {
                $itemNum = $index + 1;
                
                if (empty($item['prenda'])) {
                    $errores[] = "Ítem {$itemNum}: Prenda no especificada";
                }
                
                // ✅ Validar tallas dependiendo del tipo de item
                $tipo = $item['tipo'] ?? 'cotizacion';
                
                if ($tipo === 'prenda_nueva') {
                    // Para prendas nuevas, validar cantidad_talla (objeto)
                    $cantidadTalla = $item['cantidad_talla'] ?? [];
                    if (empty($cantidadTalla) || !is_array($cantidadTalla) || count($cantidadTalla) === 0) {
                        $errores[] = "Ítem {$itemNum}: Debe especificar cantidades por talla";
                    }
                } else {
                    // Para cotizaciones, validar tallas (array)
                    if (empty($item['tallas']) || !is_array($item['tallas']) || count($item['tallas']) === 0) {
                        $errores[] = "Ítem {$itemNum}: Debe seleccionar al menos una talla";
                    }
                }
            }
            
            if (!empty($errores)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido tiene errores',
                    'errores' => $errores,
                ], 422);
            }

            $validated = $request->validate([
                'cliente' => 'required|string',
                'asesora' => 'required|string',
                'forma_de_pago' => 'nullable|string',
                'items' => 'required|array',
            ]);

            // Obtener el usuario autenticado (asesora)
            $asesora = auth()->user();
            if (!$asesora) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            // Obtener o crear el cliente
            $cliente = \App\Models\Cliente::where('nombre', $validated['cliente'])->first();
            if (!$cliente) {
                // Si no existe, crear cliente nuevo
                $cliente = \App\Models\Cliente::create([
                    'nombre' => $validated['cliente'],
                    'estado' => 'activo',
                ]);
            }

            // Generar número de pedido único
            $ultimoPedido = \App\Models\PedidoProduccion::orderBy('id', 'desc')->first();
            $numeroPedido = ($ultimoPedido?->numero_pedido ?? 0) + 1;

            // Crear el pedido
            $pedido = \App\Models\PedidoProduccion::create([
                'numero_pedido' => $numeroPedido,
                'cliente' => $validated['cliente'],
                'cliente_id' => $cliente->id,
                'asesor_id' => $asesora->id,
                'forma_de_pago' => $validated['forma_de_pago'],
                'estado' => 'pendiente',
                'fecha_de_creacion_de_orden' => now(),
                'cantidad_total' => 0, // Se recalculará después
            ]);

            // Usar PedidoPrendaService para crear las prendas
            // Preparar prendas para guardar
            $prendasParaGuardar = [];
            $cantidadTotal = 0;
            
            foreach ($validated['items'] as $itemIndex => $item) {
                \Log::info('📦 [CrearPedidoEditableController] Procesando item:', $item);
                
                // Determinar el tipo de item
                $tipo = $item['tipo'] ?? 'cotizacion';
                
                // Determinar de_bodega: 
                // 1. Si viene explícitamente, usarlo
                // 2. Si no, mapear desde origen
                $deBodega = 1; // default: bodega
                if (isset($item['de_bodega'])) {
                    // Si viene de_bodega explícitamente, usar ese valor (puede ser 0 o 1)
                    $deBodega = (int)$item['de_bodega'];
                } else {
                    // Si no viene de_bodega, mapear desde origen
                    $origen = $item['origen'] ?? 'bodega';
                    $deBodega = $origen === 'bodega' ? 1 : 0;
                }
                
                // 🔄 PROCESAR PROCESOS CON IMÁGENES DESDE FormData
                $procesosReconstruidos = [];
                $procesosFormData = $request->file("prendas.*.procesos");
                
                if ($procesosFormData && isset($procesosFormData[$itemIndex])) {
                    $procesosByTipo = $procesosFormData[$itemIndex];
                    
                    foreach ($procesosByTipo as $tipoProceso => $datosProcesoJson) {
                        if (is_string($datosProcesoJson)) {
                            $datosProceso = json_decode($datosProcesoJson, true);
                        } else {
                            $datosProceso = $datosProcesoJson;
                        }
                        
                        if (!$datosProceso) {
                            continue;
                        }
                        
                        // Obtener imágenes del FormData para este proceso
                        $imagenesFormDataKey = "prendas.{$itemIndex}.procesos.{$tipoProceso}.imagenes";
                        $imagenesUploadedFiles = $request->file($imagenesFormDataKey) ?? [];
                        
                        // Asegurar que es array
                        if (!is_array($imagenesUploadedFiles)) {
                            $imagenesUploadedFiles = [$imagenesUploadedFiles];
                        }
                        
                        $datosProceso['imagenes'] = array_filter($imagenesUploadedFiles, function($img) {
                            return $img instanceof \Illuminate\Http\UploadedFile;
                        });
                        
                        $procesosReconstruidos[$tipoProceso] = $datosProceso;
                        
                        \Log::info("✅ Proceso reconstruido: {$tipoProceso} con " . count($datosProceso['imagenes']) . " imágenes");
                    }
                }
                
                // Convertir item a formato esperado por PedidoPrendaService
                $prendaData = [
                    'nombre_producto' => $item['prenda'],
                    'descripcion' => $item['descripcion'] ?? '',
                    'variaciones' => $item['variaciones'] ?? [],
                    'fotos' => $item['imagenes'] ?? [],
                    'procesos' => $procesosReconstruidos, // ✅ Procesos con imágenes UploadedFile
                    'origen' => $item['origen'] ?? 'bodega', // ✅ Origen de la prenda
                    'de_bodega' => $deBodega, // ✅ CAMPO FINAL CALCULADO
                    // ✅ AGREGAR OBSERVACIONES AL NIVEL SUPERIOR
                    'obs_manga' => $item['obs_manga'] ?? '',
                    'obs_bolsillos' => $item['obs_bolsillos'] ?? '',
                    'obs_broche' => $item['obs_broche'] ?? '',
                    'obs_reflectivo' => $item['obs_reflectivo'] ?? '',
                ];
                
                // ✅ Procesar tallas según el tipo de item
                if ($tipo === 'nuevo' || $tipo === 'prenda_nueva') {
                    // Para prendas nuevas, procesar cantidad_talla (objeto {genero-talla: cantidad})
                    $prendaData['cantidad_talla'] = $this->procesarCantidadTallaParaServicio($item['cantidad_talla'] ?? []);
                    $cantidadItem = $this->calcularCantidadDeCantidadTalla($item['cantidad_talla'] ?? []);
                } else {
                    // Para cotizaciones, procesar el array de tallas
                    $prendaData['cantidad_talla'] = $this->procesarTallasParaServicio($item['tallas'] ?? []);
                    $cantidadItem = $this->calcularCantidadDeTallas($item['tallas'] ?? []);
                }

                // Procesar observaciones de variaciones
                // Estructura esperada: {"manga": {"tipo": "YUT", "observacion": "YUT"}, ...}
                if (isset($item['variaciones']) && is_array($item['variaciones'])) {
                    foreach ($item['variaciones'] as $varTipo => $variacion) {
                        if (is_array($variacion)) {
                            // Extraer tipo si existe (manga, broche, bolsillos, reflectivo, etc.)
                            if (isset($variacion['tipo'])) {
                                $prendaData[$varTipo] = $variacion['tipo']; // manga, broche, etc.
                            }
                            // Extraer observación si existe
                            if (isset($variacion['observacion'])) {
                                $prendaData['obs_' . $varTipo] = $variacion['observacion'];
                                $prendaData[$varTipo . '_obs'] = $variacion['observacion'];
                            }
                        } else {
                            // Si viene como string directo, asignarlo como tipo
                            $prendaData[$varTipo] = $variacion;
                        }
                    }
                }

                // ✅ PROCESAR IDs DE RELACIONES: Color, Tela, TipoManga, TipoBroche
                // Si vienen IDs, usarlos directamente
                // Si vienen nombres, buscar o crear y obtener IDs
                
                // Procesar COLOR
                if (!empty($item['color_id'])) {
                    $prendaData['color_id'] = $item['color_id'];
                } elseif (!empty($item['color'])) {
                    try {
                        $color = $this->colorGeneroService->buscarOCrearColor($item['color']);
                        $prendaData['color_id'] = $color->id;
                        \Log::info('✅ Color creado/obtenido', ['nombre' => $item['color'], 'id' => $color->id]);
                    } catch (\Exception $e) {
                        \Log::warning('⚠️ Error procesando color', ['nombre' => $item['color'], 'error' => $e->getMessage()]);
                    }
                }
                
                // Procesar TELA
                if (!empty($item['tela_id'])) {
                    $prendaData['tela_id'] = $item['tela_id'];
                } elseif (!empty($item['tela'])) {
                    try {
                        $tela = $this->colorGeneroService->buscarOCrearTela($item['tela']);
                        $prendaData['tela_id'] = $tela->id;
                        \Log::info('✅ Tela creada/obtenida', ['nombre' => $item['tela'], 'id' => $tela->id]);
                    } catch (\Exception $e) {
                        \Log::warning('⚠️ Error procesando tela', ['nombre' => $item['tela'], 'error' => $e->getMessage()]);
                    }
                }
                
                // Procesar TIPO MANGA
                if (!empty($item['tipo_manga_id'])) {
                    $prendaData['tipo_manga_id'] = $item['tipo_manga_id'];
                } elseif (!empty($item['manga'])) {
                    try {
                        $manga = $this->colorGeneroService->buscarOCrearManga($item['manga']);
                        $prendaData['tipo_manga_id'] = $manga->id;
                        \Log::info('✅ Tipo Manga creado/obtenido', ['nombre' => $item['manga'], 'id' => $manga->id]);
                    } catch (\Exception $e) {
                        \Log::warning('⚠️ Error procesando manga', ['nombre' => $item['manga'], 'error' => $e->getMessage()]);
                    }
                }
                
                // Procesar TIPO BROCHE/BOTÓN
                if (!empty($item['tipo_broche_boton_id'])) {
                    $prendaData['tipo_broche_boton_id'] = $item['tipo_broche_boton_id'];
                } elseif (!empty($item['broche'])) {
                    try {
                        $broche = $this->colorGeneroService->buscarOCrearBroche($item['broche']);
                        $prendaData['tipo_broche_boton_id'] = $broche->id;
                        \Log::info('✅ Tipo Broche/Botón creado/obtenido', ['nombre' => $item['broche'], 'id' => $broche->id]);
                    } catch (\Exception $e) {
                        \Log::warning('⚠️ Error procesando broche', ['nombre' => $item['broche'], 'error' => $e->getMessage()]);
                    }
                }


                // Calcular cantidad total
                $cantidadTotal += $cantidadItem;
                
                $prendasParaGuardar[] = $prendaData;
            }
            
            // Guardar todas las prendas usando el servicio
            $this->pedidoPrendaService->guardarPrendasEnPedido($pedido, $prendasParaGuardar);

            // Actualizar cantidad total del pedido
            $pedido->update(['cantidad_total' => $cantidadTotal]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado correctamente',
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error en crearPedido:', [
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
     * Procesar tallas del frontend al formato esperado por el servicio
     * Preserva género: {genero: {talla: cantidad}}
     */
    private function procesarTallasParaServicio(array $tallas): array
    {
        $resultado = [];
        foreach ($tallas as $talla) {
            if (isset($talla['genero']) && isset($talla['talla']) && isset($talla['cantidad'])) {
                // Crear estructura anidada por género
                if (!isset($resultado[$talla['genero']])) {
                    $resultado[$talla['genero']] = [];
                }
                $resultado[$talla['genero']][$talla['talla']] = (int)$talla['cantidad'];
            }
        }
        return $resultado;
    }

    /**
     * ✅ Procesar cantidad_talla desde el frontend
     * Transforma {genero-talla: cantidad} a estructura de variantes
     * Ejemplo: {"dama-S": 20, "dama-M": 30} → [
     *   {genero: dama, talla: S, cantidad: 20},
     *   {genero: dama, talla: M, cantidad: 30}
     * ]
     */
    private function procesarCantidadTallaParaServicio(array $cantidadTalla): array
    {
        $resultado = [];
        
        \Log::info('🔍 [procesarCantidadTallaParaServicio] Procesando cantidad_talla', [
            'cantidad_talla_raw' => $cantidadTalla,
            'tipo' => gettype($cantidadTalla),
        ]);
        
        foreach ($cantidadTalla as $claveTalla => $cantidad) {
            // La clave viene como "genero-talla" o solo "talla"
            if (strpos($claveTalla, '-') !== false) {
                [$genero, $talla] = explode('-', $claveTalla, 2);
            } else {
                // Si no tiene género, asumir genero universal
                $genero = 'U';
                $talla = $claveTalla;
            }
            
            $resultado[] = [
                'genero' => trim($genero),
                'talla' => trim($talla),
                'cantidad' => (int)$cantidad,
            ];
        }
        
        \Log::info('✅ [procesarCantidadTallaParaServicio] Resultado transformado', [
            'resultado' => $resultado,
            'cantidad_variantes' => count($resultado),
        ]);
        
        return $resultado;
    }

    /**
     * ✅ Calcular cantidad total desde cantidad_talla
     */
    private function calcularCantidadDeCantidadTalla(array $cantidadTalla): int
    {
        $total = 0;
        foreach ($cantidadTalla as $cantidad) {
            $total += (int)$cantidad;
        }
        return $total;
    }

    /**
     * Calcular cantidad total de tallas
     */
    private function calcularCantidadDeTallas(array $tallas): int
    {
        $total = 0;
        foreach ($tallas as $talla) {
            if (isset($talla['cantidad'])) {
                $total += (int)$talla['cantidad'];
            }
        }
        return $total;
    }

    /**
     * ✅ Calcular cantidad total desde un objeto cantidad_talla
     * @param array $cantidadTalla - Objeto con forma {talla: cantidad}
     */
    private function calcularCantidadDeTallasFromObject(array $cantidadTalla): int
    {
        $total = 0;
        foreach ($cantidadTalla as $cantidad) {
            $total += (int)$cantidad;
        }
        return $total;
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
                'imagenes.*' => 'required|file|image|max:10240', // 10MB max
                'numero_pedido' => 'required|string',
            ]);

            $numeroPedido = $request->input('numero_pedido');
            $rutasGuardadas = [];

            foreach ($request->file('imagenes', []) as $index => $archivo) {
                if (!$archivo->isValid()) {
                    \Log::warning('⚠️ Archivo inválido', [
                        'numero_pedido' => $numeroPedido,
                        'index' => $index,
                    ]);
                    continue;
                }

                try {
                    $ruta = $this->procesarYGuardarImagen($archivo, $numeroPedido, $index);
                    if ($ruta) {
                        $rutasGuardadas[] = $ruta;
                    }
                } catch (\Exception $e) {
                    \Log::error('❌ Error procesando imagen', [
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
            \Log::error('❌ Error en subirImagenesPrenda', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al subir imágenes: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Procesar imagen: convertir a WebP y crear miniatura
     */
    private function procesarYGuardarImagen($archivoSubido, string $numeroPedido, int $index): ?string
    {
        try {
            use_module('InterventionImage');

            // Leer imagen
            $image = \Intervention\Image\Facades\Image::read($archivoSubido);

            // Crear nombre único
            $timestamp = now()->format('YmdHis');
            $random = substr(uniqid(), -6);
            $baseFilename = "pedido_{$numeroPedido}_img_{$index}_{$timestamp}_{$random}";

            // Crear directorio
            $dirPath = "prendas/pedidos/{$numeroPedido}";
            \Storage::disk('public')->makeDirectory($dirPath, 0755, true);

            // Guardar original
            $originalPath = "{$dirPath}/{$baseFilename}.jpg";
            \Storage::disk('public')->put($originalPath, $image->encode('jpeg', 90)->toString());

            // Guardar WebP
            $webpPath = "{$dirPath}/{$baseFilename}.webp";
            \Storage::disk('public')->put($webpPath, $image->encode('webp', 85)->toString());

            // Crear miniatura
            $thumbnail = $image->scaleDown(width: 300, height: 300);
            $thumbPath = "{$dirPath}/{$baseFilename}_thumb.webp";
            \Storage::disk('public')->put($thumbPath, $thumbnail->encode('webp', 80)->toString());

            \Log::info('✅ Imagen procesada', [
                'numero_pedido' => $numeroPedido,
                'original' => $originalPath,
                'webp' => $webpPath,
                'miniatura' => $thumbPath,
            ]);

            // Retornar array con rutas
            return json_encode([
                'ruta_original' => $originalPath,
                'ruta_webp' => $webpPath,
                'ruta_miniatura' => $thumbPath,
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error procesando imagen', [
                'numero_pedido' => $numeroPedido,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
