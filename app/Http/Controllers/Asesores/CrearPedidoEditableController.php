<?php

namespace App\Http\Controllers\Asesores;

use App\Application\DTOs\ItemPedidoDTO;
use App\Application\Services\PedidoPrendaService;
use App\Application\Services\ColorGeneroMangaBrocheService;
use App\Domain\PedidoProduccion\Services\GestionItemsPedidoService;
use App\Domain\PedidoProduccion\Services\TransformadorCotizacionService;
use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Services\PedidoEppService; // ✅ IMPORTAR SERVICIO EPP
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
        private PedidoEppService $eppService, // ✅ INYECTAR SERVICIO EPP
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
            $tipo = $item['tipo'] ?? 'prenda';

            // ✅ Validaciones diferentes según el tipo
            if ($tipo === 'epp') {
                // Para EPP: validar solo campos específicos del EPP
                if (empty($item['epp_id'])) {
                    $errores[] = "Ítem {$itemNum} (EPP): ID del EPP no especificado";
                }
                if (empty($item['cantidad']) || $item['cantidad'] <= 0) {
                    $errores[] = "Ítem {$itemNum} (EPP): Cantidad debe ser mayor a 0";
                }
                if (empty($item['talla'])) {
                    $errores[] = "Ítem {$itemNum} (EPP): Talla/medida no especificada";
                }
            } else {
                // Para PRENDAS: validar campos de prenda
                if (empty($item['prenda'])) {
                    $errores[] = "Ítem {$itemNum}: Prenda no especificada";
                }
                
                if (empty($item['tallas']) || !is_array($item['tallas']) || count($item['tallas']) === 0) {
                    $errores[] = "Ítem {$itemNum}: Debe seleccionar al menos una talla";
                }
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
            // ✅ Leer desde ambas fuentes: 'items' (para EPP) y 'prendas' (para prendas en FormData)
            $itemsDesdeItems = $request->input('items', []);
            $itemsDesdePrendas = $request->input('prendas', []);
            
            // Combinar ambos arrays
            $items = [];
            if (!empty($itemsDesdeItems)) {
                $items = array_merge($items, is_array($itemsDesdeItems) ? array_values($itemsDesdeItems) : []);
            }
            if (!empty($itemsDesdePrendas)) {
                $items = array_merge($items, is_array($itemsDesdePrendas) ? array_values($itemsDesdePrendas) : []);
            }
            
            \Log::info('📦 [CrearPedidoEditableController::crearPedido] Items recibidos:', [
                'items_count' => count($items),
                'desde_items' => count($itemsDesdeItems),
                'desde_prendas' => count($itemsDesdePrendas),
                'first_item_keys' => count($items) > 0 ? array_keys($items[0]) : [],
            ]);
            
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
                $tipo = $item['tipo'] ?? 'cotizacion';
                
                if ($tipo === 'epp') {
                    // Para EPP: validar solo campos específicos del EPP
                    if (empty($item['epp_id'])) {
                        $errores[] = "Ítem {$itemNum} (EPP): ID del EPP no especificado";
                    }
                    if (empty($item['cantidad']) || $item['cantidad'] <= 0) {
                        $errores[] = "Ítem {$itemNum} (EPP): Cantidad debe ser mayor a 0";
                    }
                    if (empty($item['talla'])) {
                        $errores[] = "Ítem {$itemNum} (EPP): Talla/medida no especificada";
                    }
                } else {
                    // Para PRENDAS: validar prenda
                    if (empty($item['prenda'])) {
                        $errores[] = "Ítem {$itemNum}: Prenda no especificada";
                    }
                }
                
                // ✅ Validar tallas dependiendo del tipo de item
                
                if ($tipo === 'epp') {
                    // EPP no necesita validación adicional aquí
                } elseif ($tipo === 'prenda_nueva') {
                    // Para prendas nuevas, validar cantidad_talla (objeto)
                    $cantidadTalla = $item['cantidad_talla'] ?? [];
                    
                    // Si es string JSON (viene de FormData), parsear
                    if (is_string($cantidadTalla)) {
                        $cantidadTalla = json_decode($cantidadTalla, true) ?? [];
                        $item['cantidad_talla'] = $cantidadTalla; // Actualizar en el array
                    }
                    
                    if (empty($cantidadTalla) || !is_array($cantidadTalla) || count($cantidadTalla) === 0) {
                        $errores[] = "Ítem {$itemNum}: Debe especificar cantidades por talla";
                    }
                } else {
                    // Para cotizaciones, validar tallas (array)
                    $tallas = $item['tallas'] ?? [];
                    
                    // Si es string JSON, parsear
                    if (is_string($tallas)) {
                        $tallas = json_decode($tallas, true) ?? [];
                        $item['tallas'] = $tallas; // Actualizar en el array
                    }
                    
                    if (empty($tallas) || !is_array($tallas) || count($tallas) === 0) {
                        $errores[] = "Ítem {$itemNum}: Debe seleccionar al menos una talla";
                    }
                }
                
                // Actualizar items con datos parseados
                $items[$index] = $item;
            }
            
            if (!empty($errores)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido tiene errores',
                    'errores' => $errores,
                ], 422);
            }

            // ✅ Validar datos básicos (no pedimos 'items' en la validación porque puede venir como 'prendas')
            $validated = $request->validate([
                'cliente' => 'required|string',
                'asesora' => 'required|string',
                'forma_de_pago' => 'nullable|string',
            ]);
            
            // Agregar los items validados al array validado
            $validated['items'] = $items;

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

            // Generar número de pedido usando tabla de secuencias
            $secuenciaRow = \Illuminate\Support\Facades\DB::table('numero_secuencias')
                ->where('tipo', 'pedido_produccion')
                ->lockForUpdate()
                ->first();
            
            $numeroPedido = $secuenciaRow?->siguiente ?? 45709;
            
            // Incrementar secuencia para el próximo pedido
            \Illuminate\Support\Facades\DB::table('numero_secuencias')
                ->where('tipo', 'pedido_produccion')
                ->increment('siguiente');

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
            $eppsParaGuardar = []; // ✅ AGREGAR ARRAY PARA EPPs
            $cantidadTotal = 0;
            
            foreach ($validated['items'] as $itemIndex => $item) {
                \Log::info('📦 [CrearPedidoEditableController] Procesando item:', $item);
                
                // Determinar el tipo de item
                $tipo = $item['tipo'] ?? 'cotizacion';
                
                // ✅ SI ES EPP, PROCESARLO SEPARADAMENTE
                if ($tipo === 'epp') {
                    \Log::info('🛡️ [CrearPedidoEditableController] Procesando EPP:', $item);
                    
                    // Construir objeto EPP para guardar
                    $eppData = [
                        'epp_id' => $item['epp_id'] ?? null,
                        'nombre' => $item['nombre'] ?? '',
                        'codigo' => $item['codigo'] ?? '',
                        'categoria' => $item['categoria'] ?? '',
                        'talla' => $item['talla'] ?? '',
                        'cantidad' => $item['cantidad'] ?? 0,
                        'observaciones' => $item['observaciones'] ?? null,
                        'imagenes' => [],  // Se llenarán a continuación
                        'tallas_medidas' => $item['tallas_medidas'] ?? $item['talla'],
                    ];
                    
                    // ✅ PROCESAR IMÁGENES DEL EPP
                    // Las imágenes vienen en FormData como archivos
                    $imagenKey = "items.{$itemIndex}.imagenes";
                    $imagenesDelEpp = $request->file($imagenKey) ?? [];
                    
                    if (is_array($imagenesDelEpp)) {
                        foreach ($imagenesDelEpp as $imagenIdx => $archivo) {
                            if ($archivo instanceof \Illuminate\Http\UploadedFile) {
                                // Guardar la imagen temporalmente
                                $path = $archivo->store('epp/temp', 'local');
                                
                                $eppData['imagenes'][] = [
                                    'archivo' => $path,
                                    'principal' => $imagenIdx === 0,
                                    'orden' => $imagenIdx,
                                ];
                                
                                \Log::info('📷 [CrearPedidoEditableController] Imagen EPP procesada:', [
                                    'path' => $path,
                                    'nombre_original' => $archivo->getClientOriginalName(),
                                ]);
                            }
                        }
                    }
                    
                    $eppsParaGuardar[] = $eppData;
                    
                    // Contar cantidad para total del pedido
                    $cantidadTotal += (int)($item['cantidad'] ?? 0);
                    
                    // Pasar al siguiente item (NO procesar como prenda)
                    continue;
                }
                
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
                
                // 🔍 DEBUG: Log item structure
                \Log::info('🔍 ESTRUCTURA DEL ITEM RECIBIDO', [
                    'itemIndex' => $itemIndex,
                    'cantidad_talla' => $item['cantidad_talla'] ?? 'NO_PRESENTE',
                    'variaciones' => $item['variaciones'] ?? 'NO_PRESENTE',
                    'keys_del_item' => array_keys($item)
                ]);
                
                // ✅ OBTENER DATOS DE PROCESOS DESDE input() (no desde file())
                $prendas = $request->input('prendas');
                
                // 🔍 DEBUG: Log procesos structure
                if ($prendas && isset($prendas[$itemIndex])) {
                    \Log::info('🔍 ESTRUCTURA DE PROCESOS RECIBIDA', [
                        'itemIndex' => $itemIndex,
                        'procesos_keys' => isset($prendas[$itemIndex]['procesos']) ? array_keys($prendas[$itemIndex]['procesos']) : [],
                        'procesos_data' => $prendas[$itemIndex]['procesos'] ?? 'NO_PRESENTE'
                    ]);
                }
                
                if ($prendas && isset($prendas[$itemIndex]) && isset($prendas[$itemIndex]['procesos'])) {
                    $procesosDatos = $prendas[$itemIndex]['procesos'];
                    
                    foreach ($procesosDatos as $tipoProceso => $procesoData) {
                        // Reconstruir datos del proceso
                        $datosProceso = [];
                        
                        // Copiar campos básicos
                        if (isset($procesoData['tipo'])) {
                            $datosProceso['tipo'] = $procesoData['tipo'];
                        }
                        if (isset($procesoData['ubicaciones'])) {
                            $datosProceso['ubicaciones'] = is_string($procesoData['ubicaciones']) 
                                ? json_decode($procesoData['ubicaciones'], true) 
                                : $procesoData['ubicaciones'];
                        }
                        if (isset($procesoData['observaciones'])) {
                            $datosProceso['observaciones'] = $procesoData['observaciones'];
                        }
                        
                        // ✅ PROCESAR TALLAS (pueden venir como JSON string)
                        $datosProceso['tallas'] = [];
                        if (isset($procesoData['tallas_dama'])) {
                            $tallasDama = is_string($procesoData['tallas_dama']) 
                                ? json_decode($procesoData['tallas_dama'], true) 
                                : $procesoData['tallas_dama'];
                            $datosProceso['tallas']['dama'] = $tallasDama ?? [];
                        }
                        if (isset($procesoData['tallas_caballero'])) {
                            $tallasCapallero = is_string($procesoData['tallas_caballero']) 
                                ? json_decode($procesoData['tallas_caballero'], true) 
                                : $procesoData['tallas_caballero'];
                            $datosProceso['tallas']['caballero'] = $tallasCapallero ?? [];
                        }
                        
                        // ✅ OBTENER IMÁGENES DEL FormData
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
                        
                        \Log::info("✅ Proceso reconstruido: {$tipoProceso}", [
                            'cantidad_imagenes' => count($datosProceso['imagenes']),
                            'ubicaciones' => $datosProceso['ubicaciones'] ?? [],
                            'tallas_dama' => $datosProceso['tallas']['dama'] ?? [],
                            'tallas_caballero' => $datosProceso['tallas']['caballero'] ?? [],
                        ]);
                    }
                }
                
                // ✅ FIX: COPIAR TALLAS DESDE cantidad_talla DEL ITEM A CADA PROCESO
                if (isset($item['cantidad_talla']) && !empty($item['cantidad_talla'])) {
                    $cantidad_talla = $item['cantidad_talla'];
                    
                    \Log::info('🔍 CANTIDAD_TALLA RECIBIDA', [
                        'raw_data' => $cantidad_talla,
                        'tipo_dato' => gettype($cantidad_talla),
                        'es_array' => is_array($cantidad_talla)
                    ]);
                    
                    $tallas_dama = [];
                    $tallas_caballero = [];
                    
                    // Si es string JSON, decodificar
                    if (is_string($cantidad_talla)) {
                        \Log::info('🔍 Decodificando cantidad_talla como JSON');
                        $cantidad_talla = json_decode($cantidad_talla, true) ?? [];
                    }
                    
                    foreach ($cantidad_talla as $clave => $cantidad) {
                        if (is_string($clave) && strpos($clave, 'dama-') === 0) {
                            $talla = str_replace('dama-', '', $clave);
                            $tallas_dama[$talla] = $cantidad;
                        } elseif (is_string($clave) && strpos($clave, 'caballero-') === 0) {
                            $talla = str_replace('caballero-', '', $clave);
                            $tallas_caballero[$talla] = $cantidad;
                        }
                    }
                    
                    \Log::info('🔍 TALLAS PARSEADAS', [
                        'tallas_dama' => $tallas_dama,
                        'tallas_caballero' => $tallas_caballero,
                        'procesosReconstruidos_count' => count($procesosReconstruidos),
                        'procesosReconstruidos_keys' => array_keys($procesosReconstruidos)
                    ]);
                    
                    foreach ($procesosReconstruidos as &$proceso) {
                        if (!isset($proceso['tallas'])) {
                            $proceso['tallas'] = [];
                        }
                        if (!empty($tallas_dama)) {
                            $proceso['tallas']['dama'] = $tallas_dama;
                        }
                        if (!empty($tallas_caballero)) {
                            $proceso['tallas']['caballero'] = $tallas_caballero;
                        }
                    }
                    
                    \Log::info('✅ Tallas copiadas a procesos', [
                        'tallas_dama' => $tallas_dama,
                        'tallas_caballero' => $tallas_caballero,
                        'procesos_actualizado_count' => count($procesosReconstruidos)
                    ]);
                } else {
                    \Log::warning('⚠️ cantidad_talla NO recibida o está VACÍA', [
                        'tiene_cantidad_talla' => isset($item['cantidad_talla']),
                        'cantidad_talla_value' => $item['cantidad_talla'] ?? null
                    ]);
                }
                
                // ✅ FIX: EXTRAER OBSERVACIONES DESDE variaciones JSON SI EXISTEN
                $obs_manga = $item['obs_manga'] ?? '';
                $obs_bolsillos = $item['obs_bolsillos'] ?? '';
                $obs_broche = $item['obs_broche'] ?? '';
                $obs_reflectivo = $item['obs_reflectivo'] ?? '';
                
                \Log::info('🔍 OBSERVACIONES INICIALES DEL ITEM', [
                    'obs_manga' => $obs_manga ?: 'VACÍO',
                    'obs_bolsillos' => $obs_bolsillos ?: 'VACÍO',
                    'obs_broche' => $obs_broche ?: 'VACÍO',
                    'obs_reflectivo' => $obs_reflectivo ?: 'VACÍO'
                ]);
                
                $variaciones_data = $item['variaciones'] ?? [];
                
                \Log::info('🔍 VARIACIONES RECIBIDAS', [
                    'tipo_dato' => gettype($variaciones_data),
                    'valor_raw' => is_string($variaciones_data) ? mb_substr($variaciones_data, 0, 200) : $variaciones_data
                ]);
                
                if (is_string($variaciones_data)) {
                    $variaciones_parsed = json_decode($variaciones_data, true);
                    \Log::info('🔍 VARIACIONES PARSEADAS COMO JSON', [
                        'decodificar_exitoso' => json_last_error() === JSON_ERROR_NONE,
                        'keys_variaciones' => is_array($variaciones_parsed) ? array_keys($variaciones_parsed) : 'NO_ES_ARRAY'
                    ]);
                    
                    if (is_array($variaciones_parsed)) {
                        if (empty($obs_manga) && isset($variaciones_parsed['manga']['observacion'])) {
                            $obs_manga = $variaciones_parsed['manga']['observacion'];
                            \Log::info('✅ manga.observacion encontrada y extraída');
                        }
                        if (empty($obs_bolsillos) && isset($variaciones_parsed['bolsillos']['observacion'])) {
                            $obs_bolsillos = $variaciones_parsed['bolsillos']['observacion'];
                            \Log::info('✅ bolsillos.observacion encontrada y extraída');
                        }
                        if (empty($obs_broche) && isset($variaciones_parsed['broche']['observacion'])) {
                            $obs_broche = $variaciones_parsed['broche']['observacion'];
                            \Log::info('✅ broche.observacion encontrada y extraída');
                        }
                        if (empty($obs_reflectivo) && isset($variaciones_parsed['reflectivo']['observacion'])) {
                            $obs_reflectivo = $variaciones_parsed['reflectivo']['observacion'];
                            \Log::info('✅ reflectivo.observacion encontrada y extraída');
                        }
                        \Log::info('✅ Observaciones extraídas de variaciones', [
                            'obs_manga' => $obs_manga,
                            'obs_bolsillos' => $obs_bolsillos,
                            'obs_broche' => $obs_broche,
                            'obs_reflectivo' => $obs_reflectivo
                        ]);
                    }
                } else {
                    \Log::info('🔍 VARIACIONES no son string, intentando como array directo', [
                        'variaciones_keys' => is_array($variaciones_data) ? array_keys($variaciones_data) : 'NO_ES_ARRAY'
                    ]);
                }
                
                // ✅ FIX: OBTENER/CREAR IDs DE TIPOS DE MANGA Y BROCHE
                $tipo_manga_id = null;
                $tipo_broche_boton_id = null;
                
                if (is_string($variaciones_data)) {
                    $variaciones_parsed = json_decode($variaciones_data, true);
                    
                    // Obtener tipo_manga_id
                    if (isset($variaciones_parsed['manga']['tipo']) && !empty($variaciones_parsed['manga']['tipo'])) {
                        $tipoMangaNombre = $variaciones_parsed['manga']['tipo'];
                        try {
                            $tipoManga = $this->colorGeneroService->buscarOCrearManga($tipoMangaNombre);
                            $tipo_manga_id = $tipoManga->id;
                            \Log::info('✅ Tipo manga obtenido/creado', [
                                'nombre' => $tipoMangaNombre,
                                'id' => $tipo_manga_id
                            ]);
                        } catch (\Exception $e) {
                            \Log::warning('⚠️ Error procesando tipo manga', [
                                'nombre' => $tipoMangaNombre,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    // Obtener tipo_broche_boton_id
                    if (isset($variaciones_parsed['broche']['tipo']) && !empty($variaciones_parsed['broche']['tipo'])) {
                        $tipoBrocheNombre = $variaciones_parsed['broche']['tipo'];
                        try {
                            $tipoBroche = $this->colorGeneroService->buscarOCrearBroche($tipoBrocheNombre);
                            $tipo_broche_boton_id = $tipoBroche->id;
                            \Log::info('✅ Tipo broche obtenido/creado', [
                                'nombre' => $tipoBrocheNombre,
                                'id' => $tipo_broche_boton_id
                            ]);
                        } catch (\Exception $e) {
                            \Log::warning('⚠️ Error procesando tipo broche', [
                                'nombre' => $tipoBrocheNombre,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
                
                // ✅ OBTENER IMÁGENES DE PRENDA DESDE FormData
                $fotosFormDataKey = "prendas.{$itemIndex}.imagenes";
                $fotosUploadedFiles = $request->file($fotosFormDataKey) ?? [];
                
                // Asegurar que es array
                if (!is_array($fotosUploadedFiles)) {
                    $fotosUploadedFiles = [$fotosUploadedFiles];
                }
                
                $fotosFiltered = array_filter($fotosUploadedFiles, function($foto) {
                    return $foto instanceof \Illuminate\Http\UploadedFile;
                });
                
                \Log::info('🎨 [FOTOS PRENDA] Procesadas', [
                    'itemIndex' => $itemIndex,
                    'cantidad_fotos' => count($fotosFiltered),
                    'formDataKey' => $fotosFormDataKey,
                ]);
                
                // ✅ OBTENER IMÁGENES DE TELAS DESDE FormData y FUSIONAR con datos existentes
                $telasFormDataKey = "prendas.{$itemIndex}.telas";
                $telasConImagenes = [];
                
                // Primero, copiar datos de telas existentes del item si los hay
                if (!empty($item['telas']) && is_array($item['telas'])) {
                    foreach ($item['telas'] as $telaIdx => $telaDatos) {
                        $telasConImagenes[$telaIdx] = is_array($telaDatos) ? $telaDatos : [];
                        // Asegurar que tenga clave 'fotos'
                        if (!isset($telasConImagenes[$telaIdx]['fotos'])) {
                            $telasConImagenes[$telaIdx]['fotos'] = [];
                        }
                    }
                }
                
                // Ahora obtener imágenes de FormData y agregarlas
                $telaFiles = $request->file($telasFormDataKey) ?? [];
                if (is_array($telaFiles)) {
                    foreach ($telaFiles as $telaIdx => $telaData) {
                        // Inicializar si no existe
                        if (!isset($telasConImagenes[$telaIdx])) {
                            $telasConImagenes[$telaIdx] = ['fotos' => []];
                        }
                        
                        // Obtener imagenes de esta tela específica
                        $imagenesTela = $request->file($telasFormDataKey . ".{$telaIdx}.imagenes") ?? [];
                        if (!is_array($imagenesTela)) {
                            $imagenesTela = [$imagenesTela];
                        }
                        
                        $imagenesTelaFiltered = array_filter($imagenesTela, function($img) {
                            return $img instanceof \Illuminate\Http\UploadedFile;
                        });
                        
                        // Agregar fotos (puede haber más de una)
                        if (!empty($imagenesTelaFiltered)) {
                            $telasConImagenes[$telaIdx]['fotos'] = array_values($imagenesTelaFiltered);
                        }
                    }
                }
                
                
                \Log::info('🧵 [FOTOS TELA] Procesadas', [
                    'itemIndex' => $itemIndex,
                    'cantidad_telas_con_fotos' => count(array_filter($telasConImagenes, function($t) { 
                        return !empty($t['imagenes']); 
                    })),
                ]);
                
                // Convertir item a formato esperado por PedidoPrendaService
                $prendaData = [
                    'nombre_producto' => $item['prenda'],
                    'descripcion' => $item['descripcion'] ?? '',
                    'variaciones' => $variaciones_data,
                    'fotos' => $fotosFiltered, // ✅ Fotos de prenda como UploadedFile
                    'procesos' => $procesosReconstruidos, // ✅ Procesos con imágenes UploadedFile
                    'origen' => $item['origen'] ?? 'bodega', // ✅ Origen de la prenda
                    'de_bodega' => $deBodega, // ✅ CAMPO FINAL CALCULADO
                    // ✅ OBSERVACIONES EXTRAÍDAS
                    'obs_manga' => $obs_manga,
                    'obs_bolsillos' => $obs_bolsillos,
                    'obs_broche' => $obs_broche,
                    'obs_reflectivo' => $obs_reflectivo,
                    // ✅ IDs DE TIPOS DE VARIACIÓN
                    'tipo_manga_id' => $tipo_manga_id,
                    'tipo_broche_boton_id' => $tipo_broche_boton_id,
                    // ✅ TELAS CON IMÁGENES
                    'telas' => $telasConImagenes,
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
                        $tela = $this->colorGeneroService->obtenerOCrearTela($item['tela']);
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
            
            // ✅ LOG DE VERIFICACIÓN ANTES DE GUARDAR
            \Log::info('📦 [CrearPedidoEditableController] Prendas listas para guardar', [
                'cantidad_prendas' => count($prendasParaGuardar),
                'prendas_estructura' => array_map(function($p) {
                    return [
                        'nombre' => $p['nombre_producto'] ?? 'SIN NOMBRE',
                        'tiene_fotos' => !empty($p['fotos']) ? count($p['fotos']) : 0,
                        'tiene_telas' => !empty($p['telas']) ? count($p['telas']) : 0,
                        'tiene_procesos' => !empty($p['procesos']) ? count($p['procesos']) : 0,
                    ];
                }, $prendasParaGuardar),
            ]);
            
            // Guardar todas las prendas usando el servicio
            $this->pedidoPrendaService->guardarPrendasEnPedido($pedido, $prendasParaGuardar);
            
            // ✅ GUARDAR EPPS SI LOS HAY
            if (!empty($eppsParaGuardar)) {
                \Log::info('🛡️ Guardando EPPs del pedido:', [
                    'cantidad_epps' => count($eppsParaGuardar),
                    'epps' => array_map(function($e) {
                        return [
                            'nombre' => $e['nombre'],
                            'cantidad' => $e['cantidad'],
                            'talla' => $e['talla'],
                        ];
                    }, $eppsParaGuardar),
                ]);
                
                try {
                    $this->eppService->guardarEppsDelPedido($pedido, $eppsParaGuardar);
                    \Log::info('✅ EPPs guardados exitosamente para pedido:', ['pedido_id' => $pedido->id]);
                } catch (\Exception $e) {
                    \Log::error('❌ Error guardando EPPs:', [
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
        // ✅ Devolver estructura: {genero: {talla: cantidad}}
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
            
            $genero = trim($genero);
            $talla = trim($talla);
            $cantidad = (int)$cantidad;
            
            // Crear estructura {genero: {talla: cantidad}}
            if (!isset($resultado[$genero])) {
                $resultado[$genero] = [];
            }
            $resultado[$genero][$talla] = $cantidad;
        }
        
        \Log::info('✅ [procesarCantidadTallaParaServicio] Resultado transformado', [
            'resultado' => $resultado,
            'estructura' => 'genero.talla.cantidad',
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
