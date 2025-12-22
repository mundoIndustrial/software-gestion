<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\LogoPedido;
use App\Models\LogoPedidoImagen;
use App\Services\Pedidos\CotizacionSearchService;
use App\Services\Pedidos\CotizacionDataExtractorService;
use App\Services\Pedidos\PedidoProduccionCreatorService;
use App\Services\Pedidos\PrendaProcessorService;
use App\DTOs\CrearPedidoProduccionDTO;
use App\DTOs\CotizacionSearchDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Controller para Pedidos de Producción - Refactorizado con SOLID
 * 
 * Responsabilidades:
 * - SRP: Solo coordina requests HTTP y respuestas
 * - DIP: Inyecta Services, no accede directamente a modelos
 * - OCP: Fácil extender con nuevas funcionalidades
 */
class PedidoProduccionController extends Controller
{
    public function __construct(
        private CotizacionSearchService $cotizacionSearch,
        private CotizacionDataExtractorService $dataExtractor,
        private PedidoProduccionCreatorService $pedidoCreator,
        private PrendaProcessorService $prendaProcessor,
    ) {}

    /**
     * Listar todos los pedidos de producción del asesor
     * 
     * @return View
     */
    public function index(): View
    {
        $tipo = request('tipo');
        
        // Si es LOGO, mostrar LogoPedidos directamente (que tienen pedido_id = null)
        if ($tipo === 'logo') {
            $pedidos = LogoPedido::where('pedido_id', null)
                ->with('procesos')
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        } else {
            // PRENDAS o TODOS: Mostrar PedidoProduccion
            $query = PedidoProduccion::where('asesor_id', auth()->id())
                ->with([
                    'prendas', 
                    'asesora',
                    'logoPedidos' => function($q) {
                        $q->with('procesos'); // Eager load procesos para obtener areaActual
                    }
                ]);

            // Si es solo PRENDAS, excluir los que tienen logo
            if ($tipo === 'prendas') {
                $query->whereDoesntHave('logoPedidos');
            }

            $pedidos = $query->orderBy('created_at', 'desc')
                ->paginate(20);
        }

        // LOG: Verificar datos enviados a la vista
        \Log::info('📋 [PedidoProduccionController] Pedidos para mostrar', [
            'total_pedidos' => $pedidos->total(),
            'filtro_tipo' => $tipo,
            'es_logo' => $tipo === 'logo',
            'muestras' => $pedidos->take(3)->map(function($pedido) {
                if (get_class($pedido) === 'App\Models\LogoPedido') {
                    return [
                        'id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'tipo' => 'logo',
                        'cliente' => $pedido->cliente,
                    ];
                } else {
                    return [
                        'id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'numero_mostrable' => $pedido->numero_pedido_mostrable,
                        'es_logo' => $pedido->esLogo(),
                        'cantidad_total_db' => $pedido->cantidad_total,
                        'prendas_count' => $pedido->prendas->count(),
                    ];
                }
            })->all()
        ]);

        return view('asesores.pedidos.index', [
            'pedidos' => $pedidos,
        ]);
    }

    /**
     * Muestra formulario para crear pedido desde cotización
     * 
     * @return View
     */
    public function mostrarFormularioCrearDesdeCotzacion(): View
    {
        // Obtener todas las cotizaciones
        $todas = $this->cotizacionSearch->obtenerTodas();

        // Convertir a DTOs para pasarlas a JavaScript
        $cotizacionesDTOs = $todas
            ->map(fn($cot) => $cot->toArray())
            ->values();

        return view('asesores.pedidos.crear-desde-cotizacion-refactorizado', [
            'cotizacionesDTOs' => $cotizacionesDTOs,
        ]);
    }

    /**
     * Crea un nuevo pedido de producción
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function crearDesdeCotizacion(Request $request): JsonResponse
    {
        \Log::info('🚀 [PedidoProduccionController] ===== INICIO CREACIÓN DE PEDIDO =====');
        \Log::info('🚀 [PedidoProduccionController] Request recibido', [
            'cotizacion_id' => $request->input('cotizacion_id'),
            'forma_de_pago' => $request->input('forma_de_pago'),
            'prendas_del_formulario' => count($request->input('prendas', [])),
        ]);

        try {
            // Validar request
            $validated = $request->validate([
                'cotizacion_id' => 'required|integer|exists:cotizaciones,id',
                'forma_de_pago' => 'nullable|string',
                'prendas' => 'nullable|array',
                'reflectivo_fotos_ids' => 'nullable|array',
            ]);

            \Log::info('🚀 [PedidoProduccionController] Validación exitosa', $validated);

            // Obtener cotización
            $cotizacion = Cotizacion::with('cliente')->findOrFail($validated['cotizacion_id']);

            \Log::info('🔍 [PedidoProduccionController] Cotización obtenida', [
                'cotizacion_id' => $cotizacion->id,
                'especificaciones' => $cotizacion->especificaciones,
            ]);

            // ✅ NUEVO: Usar prendas del formulario si vienen
            $prendas = $request->input('prendas');
            if (!empty($prendas) && is_array($prendas)) {
                \Log::info('🎯 [PedidoProduccionController] Usando prendas del formulario editado', [
                    'prendas_recibidas' => count($prendas),
                ]);
                // Usar directamente los prendas del formulario
                $datosExtraidos = [
                    'prendas' => $prendas,
                    'cliente' => $cotizacion->cliente,
                    'cliente_id' => $cotizacion->cliente_id,
                ];
            } else {
                // Fallback: Extraer de la cotización si no hay prendas del formulario
                \Log::info('🔄 [PedidoProduccionController] No hay prendas del formulario, extrayendo de cotización');
                $datosExtraidos = $this->dataExtractor->extraerDatos($cotizacion);
            }

            // Extraer forma de pago de especificaciones
            $formaDePago = null;
            if ($cotizacion->especificaciones) {
                $especificaciones = is_array($cotizacion->especificaciones) 
                    ? $cotizacion->especificaciones 
                    : json_decode($cotizacion->especificaciones, true);
                
                \Log::info('🔍 [PedidoProduccionController] Especificaciones decodificadas', [
                    'especificaciones' => $especificaciones,
                ]);

                if (isset($especificaciones['forma_pago'])) {
                    $formaPagoArray = $especificaciones['forma_pago'];
                    \Log::info('🔍 [PedidoProduccionController] forma_pago encontrada en especificaciones', [
                        'forma_pago_array' => $formaPagoArray,
                        'es_array' => is_array($formaPagoArray),
                    ]);
                    
                    // Si es un array, tomar el primer elemento (si existe)
                    if (is_array($formaPagoArray) && !empty($formaPagoArray)) {
                        $formaDePago = $formaPagoArray[0];
                    } elseif (!is_array($formaPagoArray)) {
                        $formaDePago = $formaPagoArray;
                    }
                }
            }

            // Usar forma_de_pago del request si viene
            if (!empty($validated['forma_de_pago'])) {
                \Log::info('🔍 [PedidoProduccionController] Usando forma_de_pago del request', [
                    'forma_de_pago_request' => $validated['forma_de_pago'],
                ]);
                $formaDePago = $validated['forma_de_pago'];
            }

            \Log::info('🔍 [PedidoProduccionController] Forma de pago final antes de DTO', [
                'forma_de_pago_final' => $formaDePago,
            ]);

            // Verificar si es una cotización de tipo LOGO al inicio
            $esLogoRequest = filter_var($request->input('esLogo', false), FILTER_VALIDATE_BOOLEAN);
            $tipoCotizacion = $cotizacion->tipo_cotizacion_codigo ?? null;
            $esCotizacionLogo = $esLogoRequest || $tipoCotizacion === 'L';

            \Log::info('🔍 [DEBUG] Detección de tipo de cotización', [
                'esLogo_request' => $esLogoRequest,
                'tipo_cotizacion_codigo' => $tipoCotizacion,
                'esCotizacionLogo' => $esCotizacionLogo
            ]);

            // Si es LOGO, manejar de forma independiente
            if ($esCotizacionLogo) {
                \Log::info('🎨 [PedidoProduccionController] Iniciando creación de pedido LOGO');
                
                // Generar número de pedido con formato seguro
                $numeroPedido = 'LOGO-' . date('Ymd-His') . '-' . rand(100, 999);
                
                \Log::info('🔢 Número de pedido generado para LOGO', ['numero_pedido' => $numeroPedido]);
                
                $formaDePago = $request->input('forma_de_pago', '');

                // Crear el logo_pedido directamente
                $logoPedido = new LogoPedido([
                    'pedido_id' => null, // Establecer como null ya que no hay pedido de producción
                    'logo_cotizacion_id' => $request->input('logo_cotizacion_id', $cotizacion->id),
                    'cliente' => $cotizacion->cliente->nombre ?? 'Cliente no especificado',
                    'asesora' => auth()->user()->name,
                    'forma_de_pago' => $formaDePago,
                    'encargado_orden' => auth()->user()->name,
                    'fecha_de_creacion_de_orden' => now(),
                    'estado' => \App\Enums\EstadoPedido::PENDIENTE_SUPERVISOR->value,
                    'area' => 'creacion_de_orden',
                    'numero_cotizacion' => $cotizacion->numero_cotizacion,
                    'cotizacion_id' => $cotizacion->id,
                    'numero_pedido' => $numeroPedido,
                    'descripcion' => $request->input('descripcion', 'Pedido de LOGO'),
                    'tecnicas' => $request->input('tecnicas', []),
                    'ubicaciones' => $request->input('ubicaciones', []),
                    'observaciones_tecnicas' => $request->input('observaciones_tecnicas', ''),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $logoPedido->save();

                // Procesar imágenes si existen
                if ($request->has('imagenes') && is_array($request->imagenes)) {
                    foreach ($request->imagenes as $imagen) {
                        try {
                            $path = $imagen->store('public/bordado/pedidos/' . $logoPedido->id);
                            $logoPedido->imagenes()->create([
                                'ruta' => str_replace('public/', 'storage/', $path),
                                'nombre_original' => $imagen->getClientOriginalName(),
                                'tipo' => $imagen->getClientMimeType(),
                                'tamanio' => $imagen->getSize(),
                            ]);
                        } catch (\Exception $e) {
                            \Log::error('Error al guardar imagen del logo: ' . $e->getMessage());
                            continue;
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Pedido de LOGO creado exitosamente',
                    'logo_pedido_id' => $logoPedido->id,
                    'pedido_id' => $logoPedido->id,
                    'logo_cotizacion_id' => $logoPedido->logo_cotizacion_id,
                    'pedido' => [
                        'id' => $logoPedido->id,
                        'numero_pedido' => $logoPedido->numero_pedido,
                        'tipo' => 'logo',
                        'fecha_creacion' => $logoPedido->fecha_de_creacion_de_orden->format('Y-m-d H:i:s')
                    ]
                ]);
            }

            // Si no es LOGO, continuar con el flujo normal
            $dto = CrearPedidoProduccionDTO::fromRequest([
                'cotizacion_id' => $validated['cotizacion_id'],
                'prendas' => $datosExtraidos['prendas'],
                'cliente' => $datosExtraidos['cliente'],
                'cliente_id' => $datosExtraidos['cliente_id'],
                'forma_de_pago' => $formaDePago,
            ]);

            \Log::info('🔍 [PedidoProduccionController] DTO creado', [
                'dto_forma_de_pago' => $dto->formaDePago,
            ]);

            // Validar DTO
            if (!$dto->esValido()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos: No hay prendas con cantidades válidas'
                ], 422);
            }

            // Crear pedido usando Service
            \Log::info('🚀 [PedidoProduccionController] Llamando a pedidoCreator->crear()');
            
            $pedido = $this->pedidoCreator->crear(
                $dto,
                auth()->id()
            );


            \Log::info('🚀 [PedidoProduccionController] Pedido creado exitosamente', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            return response()->json([
                'success' => true,
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'mensaje' => '✅ Pedido creado exitosamente'
            ], 201);

        } catch (\Throwable $e) {
            \Log::error('❌ [PedidoProduccionController] Error al crear pedido:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesa fotos de reflectivo para un pedido
     */
    private function procesarFotosReflectivo(array $fotosIds, int $cotizacionId, array &$fotosData): void
    {
        if (empty($fotosIds)) {
            return;
        }

        \Log::info('📸 [PedidoProduccionController] Procesando fotos de reflectivo', [
            'fotos_ids' => $fotosIds
        ]);
        
        // Obtener las fotos del reflectivo desde la BD
        $reflectivo = \App\Models\ReflectivoCotizacion::where('cotizacion_id', $cotizacionId)->first();
        
        if (!$reflectivo) {
            return;
        }

        $fotosReflectivo = \App\Models\ReflectivoCotizacionFoto::whereIn('id', $fotosIds)
            ->where('reflectivo_cotizacion_id', $reflectivo->id)
            ->get();
        
        \Log::info('📸 Fotos de reflectivo encontradas', [
            'cantidad' => $fotosReflectivo->count()
        ]);
        
        // Agregar las fotos del reflectivo a la primera prenda (índice 0)
        if ($fotosReflectivo->count() > 0) {
            if (!isset($fotosData[0])) {
                $fotosData[0] = [];
            }
            
            foreach ($fotosReflectivo as $foto) {
                $fotosData[0][] = [
                    'url' => '/storage/' . ltrim($foto->ruta_webp ?? $foto->ruta_original, '/'),
                    'ruta_original' => $foto->ruta_original,
                    'ruta_webp' => $foto->ruta_webp,
                    'orden' => $foto->orden ?? 0,
                ];
            }
            
            \Log::info('✅ Fotos de reflectivo agregadas a fotosData[0]', [
                'total_fotos_prenda_0' => count($fotosData[0])
            ]);
        }
    }

    /**
     * Obtiene próximo número de pedido
    /**
     * Obtiene el número de pedido siguiente
     * 
     * @return JsonResponse
     */
    public function obtenerProximoNumero(): JsonResponse
    {
        return response()->json([
            'siguiente_pedido' => $this->pedidoCreator->obtenerProximoNumero()
        ]);
    }

    /**
     * Guarda las fotos seleccionadas por el usuario para un pedido
     * Solo guarda las fotos que se envíen en el array (respeta lo que el usuario eliminó)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function guardarFotosPedido(Request $request): JsonResponse
    {
        \Log::info('🟢 [PedidoProduccionController::guardarFotosPedido] Iniciando guardado de fotos');
        
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'fotos' => 'nullable|array',
                'fotos.*.ruta_original' => 'nullable|string',
                'fotos.*.ruta_webp' => 'nullable|string',
                'telas' => 'nullable|array',
                'logos' => 'nullable|array',
            ]);

            $numeroPedido = $validated['numero_pedido'];
            $fotos = $validated['fotos'] ?? [];
            $telas = $validated['telas'] ?? [];
            $logos = $validated['logos'] ?? [];

            \Log::info('🟢 [guardarFotosPedido] Datos recibidos', [
                'numero_pedido' => $numeroPedido,
                'cantidad_fotos' => count($fotos),
                'cantidad_telas' => count($telas),
                'cantidad_logos' => count($logos),
            ]);

            // Obtener prendas del pedido
            $prendas = \App\Models\PrendaPedido::where('numero_pedido', $numeroPedido)
                ->get();

            if ($prendas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron prendas para este pedido'
                ], 404);
            }

            \Log::info('✅ [guardarFotosPedido] Todas las fotos guardadas exitosamente');

            return response()->json([
                'success' => true,
                'message' => 'Fotos guardadas correctamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ [guardarFotosPedido] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar fotos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene datos completos de una cotización
     * Utilizado por AJAX desde JavaScript
     * 
     * @param int $cotizacionId
     * @return JsonResponse
     */
    public function obtenerDatosCotizacion(int $cotizacionId): JsonResponse
    {
        try {
            $cotizacion = $this->cotizacionSearch->obtenerPorId($cotizacionId);

            if (!$cotizacion) {
                return response()->json([
                    'error' => 'Cotización no encontrada'
                ], 404);
            }

            // Obtener logo si existe
            $logoData = null;
            if ($cotizacion->logo) {
                $seccionesRaw = $cotizacion->logo->secciones;
                
                // Parsear secciones si vienen como JSON string
                $seccionesArray = $seccionesRaw;
                if (is_string($seccionesRaw)) {
                    try {
                        $seccionesArray = json_decode($seccionesRaw, true);
                    } catch (\Exception $e) {
                        \Log::warning('Error parseando secciones del logo:', ['error' => $e->getMessage()]);
                        $seccionesArray = [];
                    }
                }
                
                // Convertir NULL a array vacío
                if ($seccionesArray === null) {
                    $seccionesArray = [];
                }
                
                \Log::info('📍 Logo secciones datos:', [
                    'cotizacion_id' => $cotizacion->id,
                    'logo_id' => $cotizacion->logo->id,
                    'raw' => $seccionesRaw,
                    'tipo_raw' => gettype($seccionesRaw),
                    'parseado' => $seccionesArray,
                    'es_array' => is_array($seccionesArray),
                    'count' => is_array($seccionesArray) ? count($seccionesArray) : 0
                ]);
                
                $logoData = [
                    'id' => $cotizacion->logo->id,
                    'descripcion' => $cotizacion->logo->descripcion,
                    'secciones' => $seccionesArray ?? [],  // Secciones parseadas
                    'ubicaciones' => $seccionesArray ?? [],  // Compatibilidad con viejos datos
                    'tecnicas' => $cotizacion->logo->tecnicas ?? [],
                    'observaciones_tecnicas' => $cotizacion->logo->observaciones_tecnicas,
                    'observaciones_generales' => $cotizacion->logo->observaciones_generales,
                    'fotos' => $cotizacion->logo->fotos ? $cotizacion->logo->fotos->map(function($foto) {
                        return [
                            'id' => $foto->id,
                            'url' => $foto->url ?? $foto->ruta_webp ?? $foto->ruta_original,
                            'ruta_original' => $foto->ruta_original,
                            'ruta_webp' => $foto->ruta_webp,
                            'ruta_miniatura' => $foto->ruta_miniatura,
                        ];
                    })->toArray() : [],
                ];
            }

            // Preparar respuesta base
            $response = [
                'id' => $cotizacion->id,
                'numero' => $cotizacion->numero_cotizacion,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'cliente' => $cotizacion->cliente,
                'asesora' => $cotizacion->asesora,
                'forma_pago' => $cotizacion->forma_pago ?? '',
                'tipo_cotizacion_codigo' => $cotizacion->tipo_cotizacion_codigo ?? '',
                'tipo_cotizacion_codigo' => $cotizacion->tipoCotizacion->codigo ?? 'PL',
                'logo' => $logoData,
                'prendas' => $cotizacion->prendasCotizaciones->map(function($prenda) {
                    // Mapear fotos para que contengan URLs correctas
                    $fotosFormato = [];
                    if ($prenda->fotos && count($prenda->fotos) > 0) {
                        $fotosFormato = $prenda->fotos->map(function($foto) {
                            return [
                                'url' => $foto->url ?? $foto->ruta_webp ?? $foto->ruta_original,
                                'ruta_original' => $foto->ruta_original,
                                'ruta_webp' => $foto->ruta_webp,
                                'ruta_miniatura' => $foto->ruta_miniatura,
                                'ancho' => $foto->ancho,
                                'alto' => $foto->alto,
                                'tamaño' => $foto->tamaño,
                            ];
                        })->toArray();
                    }

                    // Mapear fotos de telas
                    $telasFormato = [];
                    if ($prenda->telaFotos && count($prenda->telaFotos) > 0) {
                        $telasFormato = $prenda->telaFotos->map(function($tela) {
                            $fotosTelaArray = [];
                            if ($tela->fotos && count($tela->fotos) > 0) {
                                $fotosTelaArray = $tela->fotos->map(function($foto) {
                                    return [
                                        'url' => $foto->url ?? $foto->ruta_webp ?? $foto->ruta_original,
                                        'ruta_original' => $foto->ruta_original,
                                        'ruta_webp' => $foto->ruta_webp,
                                        'ruta_miniatura' => $foto->ruta_miniatura,
                                        'ancho' => $foto->ancho,
                                        'alto' => $foto->alto,
                                        'tamaño' => $foto->tamaño,
                                    ];
                                })->toArray();
                            }

                            return [
                                'tela_id' => $tela->tela_id,
                                'color_id' => $tela->color_id,
                                'tela_nombre' => $tela->telaPrenda?->nombre,
                                'color_nombre' => $tela->colorPrenda?->nombre,
                                'fotos' => $fotosTelaArray,
                            ];
                        })->toArray();
                    }

                    return [
                        'nombre_producto' => $prenda->nombre_producto,
                        'descripcion' => $prenda->descripcion,
                        'tallas' => $prenda->tallas ?? [],
                        'fotos' => $fotosFormato,  // ✅ Fotos formateadas
                        'telaFotos' => $telasFormato,  // ✅ Fotos de telas formateadas
                        'variantes' => $prenda->variantes ?? [],
                    ];
                })->toArray(),
            ];

            // ✅ Agregar datos del LOGO si existe
            if ($cotizacion->logoCotizacion) {
                $logoCotizacion = $cotizacion->logoCotizacion;
                
                // Formatear fotos del logo
                $fotosLogo = [];
                if ($logoCotizacion->fotos && count($logoCotizacion->fotos) > 0) {
                    $fotosLogo = $logoCotizacion->fotos->map(function($foto) {
                        return [
                            'id' => $foto->id,
                            'url' => $foto->url ?? $foto->ruta_webp ?? $foto->ruta_original,
                            'ruta_original' => $foto->ruta_original,
                            'ruta_webp' => $foto->ruta_webp,
                            'ruta_miniatura' => $foto->ruta_miniatura,
                            'orden' => $foto->orden,
                        ];
                    })->toArray();
                }

                $response['logo'] = [
                    'id' => $logoCotizacion->id,
                    'descripcion' => $logoCotizacion->descripcion ?? '',
                    'tecnicas' => $logoCotizacion->tecnicas ?? [],
                    'observaciones_tecnicas' => $logoCotizacion->observaciones_tecnicas ?? '',
                    'secciones' => $logoCotizacion->secciones ?? [],  // ✅ AGREGAR SECCIONES
                    'observaciones_generales' => $logoCotizacion->observaciones_generales ?? [],
                    'fotos' => $fotosLogo,
                    'tipo_venta' => $logoCotizacion->tipo_venta ?? '',
                ];
            }

            return response()->json($response);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guarda un pedido de LOGO con toda su información
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function guardarLogoPedido(Request $request): JsonResponse
    {
        \Log::info('🎨 [PedidoProduccionController] ===== GUARDANDO PEDIDO LOGO =====');
        \Log::info('🎨 [PedidoProduccionController] Datos crudos recibidos:', $request->all());

        try {
            // Validar datos (pedido_id opcional para casos standalone)
            $validated = $request->validate([
                // pedido_id es el id de logo_pedidos creado en el paso 1; si viene null, creamos uno nuevo
                'pedido_id' => 'nullable|integer|exists:logo_pedidos,id',
                'logo_cotizacion_id' => 'required|integer|exists:logo_cotizaciones,id',
                'descripcion' => 'nullable|string',
                'tecnicas' => 'nullable|array',
                'observaciones_tecnicas' => 'nullable|string',
                'ubicaciones' => 'nullable|array',
                'fotos' => 'nullable|array',
            ]);

            \Log::info('🎨 [PedidoProduccionController] Datos validados', [
                'pedido_id' => $validated['pedido_id'],
                'descripcion_recibida' => $validated['descripcion'] ?? 'SIN DESCRIPCIÓN',
                'tecnicas_count' => count($validated['tecnicas'] ?? []),
                'ubicaciones_count' => count($validated['ubicaciones'] ?? []),
                'fotos_count' => count($validated['fotos'] ?? []),
            ]);

            // Recuperar el LogoPedido creado en el paso 1 (si viene), o crear uno standalone
            $logoPedido = null;
            if (!empty($validated['pedido_id'])) {
                $logoPedido = LogoPedido::find($validated['pedido_id']);
            }

            if (!$logoPedido) {
                // Crear uno nuevo con defaults para pedidos solo-logo
                $numeroPedido = 'LOGO-' . date('Ymd-His') . '-' . rand(100, 999);
                $logoPedido = LogoPedido::create([
                    'pedido_id' => null,
                    'logo_cotizacion_id' => $validated['logo_cotizacion_id'],
                    'numero_pedido' => $numeroPedido,
                    'cotizacion_id' => $request->input('cotizacion_id'),
                    'numero_cotizacion' => $request->input('numero_cotizacion', ''),
                    'cliente' => $request->input('cliente', 'Cliente no especificado'),
                    'asesora' => auth()->user()->name,
                    'forma_de_pago' => $request->input('forma_de_pago', ''),
                    'encargado_orden' => auth()->user()->name,
                    'fecha_de_creacion_de_orden' => now(),
                    'estado' => \App\Enums\EstadoPedido::PENDIENTE_SUPERVISOR->value,
                    'area' => 'creacion_de_orden',
                    'descripcion' => $validated['descripcion'] ?? '',
                    'tecnicas' => $validated['tecnicas'] ?? [],
                    'observaciones_tecnicas' => $validated['observaciones_tecnicas'] ?? '',
                    'ubicaciones' => $validated['ubicaciones'] ?? [],
                    'observaciones' => $request->input('observaciones', ''),
                ]);
            } else {
                // Si por alguna razón no tiene numero_pedido, generar uno
                if (empty($logoPedido->numero_pedido)) {
                    $logoPedido->numero_pedido = 'LOGO-' . date('Ymd-His') . '-' . rand(100, 999);
                }
                // Completar datos faltantes y guardar
                $logoPedido->descripcion = $validated['descripcion'] ?? $logoPedido->descripcion ?? '';
                $logoPedido->tecnicas = $validated['tecnicas'] ?? $logoPedido->tecnicas ?? [];
                $logoPedido->observaciones_tecnicas = $validated['observaciones_tecnicas'] ?? $logoPedido->observaciones_tecnicas ?? '';
                $logoPedido->ubicaciones = $validated['ubicaciones'] ?? $logoPedido->ubicaciones ?? [];
                // Asegurar que tenga área y estado correcto
                if (empty($logoPedido->area)) {
                    $logoPedido->area = 'creacion_de_orden';
                }
                if (empty($logoPedido->estado) || $logoPedido->estado === 'pendiente') {
                    $logoPedido->estado = \App\Enums\EstadoPedido::PENDIENTE_SUPERVISOR->value;
                }
                $logoPedido->save();
            }

            \Log::info('🎨 LogoPedido creado exitosamente', [
                'logo_pedido_id' => $logoPedido->id,
                'numero_pedido' => $logoPedido->numero_pedido,
                'descripcion_guardada' => $logoPedido->descripcion ?? 'SIN DESCRIPCIÓN',
                'tecnicas_guardadas' => $logoPedido->tecnicas,
                'observaciones_guardadas' => $logoPedido->observaciones_tecnicas,
            ]);

            // Guardar las imágenes
            if (!empty($validated['fotos']) && is_array($validated['fotos'])) {
                $orden = 1;

                foreach ($validated['fotos'] as $fotoData) {
                    try {
                        // Determinar si es una imagen existente o nueva
                        $isExisting = isset($fotoData['existing']) && $fotoData['existing'] === true;

                        if ($isExisting && isset($fotoData['id'])) {
                            // Es una foto existente de la cotización
                            // Solo crear referencia en la tabla logo_pedido_imagenes
                            LogoPedidoImagen::create([
                                'logo_pedido_id' => $logoPedido->id,
                                'nombre_archivo' => $fotoData['nombre'] ?? 'imagen_existente',
                                'url' => $fotoData['url'],
                                'ruta_original' => $fotoData['ruta_original'] ?? null,
                                'ruta_webp' => $fotoData['ruta_webp'] ?? null,
                                'tipo_archivo' => $fotoData['tipo'] ?? 'image/jpeg',
                                'tamaño_archivo' => $fotoData['tamaño'] ?? 0,
                                'orden' => $orden++,
                            ]);

                            \Log::info('🎨 Imagen existente referenciada', [
                                'imagen_id' => $fotoData['id'],
                                'orden' => $orden - 1
                            ]);

                        } else {
                            // Es una imagen nueva cargada por el usuario
                            // Convertir base64 a archivo físico
                            if (isset($fotoData['preview']) && strpos($fotoData['preview'], 'base64') !== false) {
                                // Es base64
                                $base64String = preg_replace('#^data:image/\w+;base64,#i', '', $fotoData['preview']);
                                $imageData = base64_decode($base64String);
                                
                                // Generar nombre único
                                $nombreArchivo = 'logo_' . $logoPedido->id . '_' . time() . '_' . rand(1000, 9999) . '.jpg';
                                $rutaStorage = 'logo_pedidos/' . $logoPedido->id . '/' . $nombreArchivo;
                                
                                // Crear directorio si no existe
                                $directorioCompleto = storage_path('app/logo_pedidos/' . $logoPedido->id);
                                if (!is_dir($directorioCompleto)) {
                                    mkdir($directorioCompleto, 0755, true);
                                }

                                // Guardar archivo
                                file_put_contents(storage_path('app/' . $rutaStorage), $imageData);

                                \Log::info('🎨 Imagen nueva guardada en storage', [
                                    'ruta' => $rutaStorage,
                                    'tamaño' => strlen($imageData)
                                ]);

                                // Crear registro en base de datos
                                LogoPedidoImagen::create([
                                    'logo_pedido_id' => $logoPedido->id,
                                    'nombre_archivo' => $nombreArchivo,
                                    'url' => '/storage/' . $rutaStorage,
                                    'ruta_original' => $rutaStorage,
                                    'ruta_webp' => null,
                                    'tipo_archivo' => 'image/jpeg',
                                    'tamaño_archivo' => strlen($imageData),
                                    'orden' => $orden++,
                                ]);

                                \Log::info('🎨 Referencia de imagen nueva creada en BD', [
                                    'orden' => $orden - 1
                                ]);
                            }
                        }
                    } catch (\Throwable $e) {
                        \Log::error('❌ Error procesando foto', [
                            'error' => $e->getMessage(),
                            'foto_data' => $fotoData
                        ]);
                        // Continuar con la siguiente foto
                        continue;
                    }
                }
            }

            \Log::info('✅ [PedidoProduccionController] LOGO Pedido guardado completamente', [
                'logo_pedido_id' => $logoPedido->id,
                'numero_pedido' => $logoPedido->numero_pedido,
                'imagenes_totales' => $logoPedido->imagenes()->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'LOGO Pedido guardado correctamente',
                'logo_pedido' => [
                    'id' => $logoPedido->id,
                    'numero_pedido' => $logoPedido->numero_pedido,
                    'descripcion' => $logoPedido->descripcion,
                    'tecnicas' => $logoPedido->tecnicas,
                    'ubicaciones' => $logoPedido->ubicaciones,
                    'observaciones_tecnicas' => $logoPedido->observaciones_tecnicas,
                    'imagenes_count' => $logoPedido->imagenes()->count(),
                ]
            ]);

        } catch (\Throwable $e) {
            \Log::error('❌ Error guardando LOGO Pedido', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el LOGO Pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar foto de logo de cotización
     * 
     * @param int $cotizacion_id ID de la cotización
     * @param Request $request
     * @return JsonResponse
     */
    public function eliminarFotoLogo($cotizacion_id, Request $request): JsonResponse
    {
        try {
            $fotoId = $request->input('foto_id');
            
            if (!$fotoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de foto no proporcionado'
                ], 400);
            }

            // Buscar la cotización y verificar que pertenece al asesor actual
            $cotizacion = Cotizacion::where('id', $cotizacion_id)
                ->where('asesor_id', auth()->id())
                ->first();

            if (!$cotizacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cotización no encontrada'
                ], 404);
            }

            // Buscar y eliminar la foto del logo
            if ($cotizacion->logoCotizacion && $cotizacion->logoCotizacion->fotos) {
                $foto = $cotizacion->logoCotizacion->fotos()
                    ->where('id', $fotoId)
                    ->first();

                if ($foto) {
                    // Eliminar archivo físico si existe
                    if ($foto->ruta_original && file_exists(storage_path('app/' . $foto->ruta_original))) {
                        unlink(storage_path('app/' . $foto->ruta_original));
                    }
                    if ($foto->ruta_webp && file_exists(storage_path('app/' . $foto->ruta_webp))) {
                        unlink(storage_path('app/' . $foto->ruta_webp));
                    }

                    // Eliminar registro de la BD
                    $foto->delete();

                    return response()->json([
                        'success' => true,
                        'message' => 'Foto eliminada correctamente'
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Foto no encontrada'
            ], 404);

        } catch (\Throwable $e) {
            \Log::error('Error eliminando foto de logo:', [
                'error' => $e->getMessage(),
                'cotizacion_id' => $cotizacion_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la foto'
            ], 500);
        }
    }

    /**
     * Eliminar un pedido de producción
     * 
     * @param int $pedido_id
     * @return JsonResponse
     */
    public function eliminarPedido(int $pedido_id): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Primero intentar eliminar un LogoPedido
            $logoPedido = LogoPedido::find($pedido_id);
            if ($logoPedido) {
                \Log::info('🎨 Eliminando LogoPedido', ['logo_pedido_id' => $logoPedido->id]);
                
                // Eliminar imágenes asociadas
                if ($logoPedido->imagenes) {
                    foreach ($logoPedido->imagenes as $imagen) {
                        if ($imagen->ruta_original && \Storage::exists($imagen->ruta_original)) {
                            \Storage::delete($imagen->ruta_original);
                        }
                        $imagen->delete();
                    }
                }

                // Eliminar procesos asociados
                if ($logoPedido->procesos) {
                    foreach ($logoPedido->procesos as $proceso) {
                        $proceso->delete();
                    }
                }

                // Eliminar el pedido LOGO
                $logoPedido->delete();
                
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Pedido de LOGO eliminado correctamente'
                ]);
            }

            // Si no es LogoPedido, intentar PedidoProduccion
            $pedido = PedidoProduccion::findOrFail($pedido_id);

            // Verificar que el pedido pertenezca al asesor autenticado
            if ($pedido->asesor_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar este pedido'
                ], 403);
            }

            \Log::info('🗑️ Eliminando PedidoProduccion', ['pedido_id' => $pedido->id]);

            // Eliminar imágenes asociadas
            if ($pedido->fotospedido) {
                foreach ($pedido->fotospedido as $foto) {
                    // Eliminar archivo de storage si existe
                    if ($foto->nombre_archivo) {
                        $path = 'public/pedidos/' . $foto->nombre_archivo;
                        if (\Storage::exists($path)) {
                            \Storage::delete($path);
                        }
                    }
                    $foto->delete();
                }
            }

            // Eliminar prendas asociadas
            if ($pedido->prendas) {
                foreach ($pedido->prendas as $prenda) {
                    $prenda->delete();
                }
            }

            // Eliminar el pedido
            $pedido->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido eliminado correctamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error eliminando pedido:', [
                'error' => $e->getMessage(),
                'pedido_id' => $pedido_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }
}
