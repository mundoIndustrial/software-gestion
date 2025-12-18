<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Services\Pedidos\CotizacionSearchService;
use App\Services\Pedidos\CotizacionDataExtractorService;
use App\Services\Pedidos\PedidoProduccionCreatorService;
use App\Services\Pedidos\PrendaProcessorService;
use App\DTOs\CrearPedidoProduccionDTO;
use App\DTOs\CotizacionSearchDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

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
        $pedidos = PedidoProduccion::where('asesor_id', auth()->id())
            ->with('prendas', 'asesora')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // LOG: Verificar datos enviados a la vista
        \Log::info('📋 [PedidoProduccionController] Pedidos para mostrar', [
            'total_pedidos' => $pedidos->total(),
            'prendas_totales' => $pedidos->sum(function($p) { return $p->prendas->count(); }),
            'muestras' => $pedidos->take(3)->map(function($pedido) {
                return [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cantidad_total_db' => $pedido->cantidad_total,
                    'prendas_count' => $pedido->prendas->count(),
                    'prendas' => $pedido->prendas->map(function($prenda) {
                        return [
                            'nombre' => $prenda->nombre_prenda,
                            'cantidad_acceso' => $prenda->cantidad,
                            'cantidad_talla' => $prenda->cantidad_talla,
                        ];
                    })->all()
                ];
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

            // Crear DTO con los datos extraídos
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

            // ✅ GUARDAR LAS FOTOS SELECCIONADAS POR EL USUARIO
            if (!empty($validated['prendas'])) {
                try {
                    \Log::info('📸 [PedidoProduccionController] Guardando fotos seleccionadas', [
                        'numero_pedido' => $pedido->numero_pedido,
                        'total_prendas' => count($validated['prendas']),
                    ]);

                    // Preparar datos de fotos por prenda
                    $fotosData = [];
                    $telasData = [];
                    $logosData = [];

                    foreach ($validated['prendas'] as $indexPrenda => $prenda) {
                        // Recopilar fotos de esta prenda
                        $fotosData[$indexPrenda] = $prenda['fotos'] ?? [];
                        $telasData[$indexPrenda] = $prenda['telas'] ?? [];
                        $logosData[$indexPrenda] = $prenda['logos'] ?? [];

                        \Log::info('📸 Fotos preparadas para prenda', [
                            'index' => $indexPrenda,
                            'fotos' => count($fotosData[$indexPrenda]),
                            'telas' => count($telasData[$indexPrenda]),
                            'logos' => count($logosData[$indexPrenda]),
                        ]);
                    }

                    // Si hay fotos de reflectivo seleccionadas, agregarlas a la primera prenda
                    if (!empty($validated['reflectivo_fotos_ids'])) {
                        \Log::info('📸 [PedidoProduccionController] Procesando fotos de reflectivo', [
                            'fotos_ids' => $validated['reflectivo_fotos_ids']
                        ]);
                        
                        // Obtener las fotos del reflectivo desde la BD
                        $reflectivo = \App\Models\ReflectivoCotizacion::where('cotizacion_id', $validated['cotizacion_id'])->first();
                        
                        if ($reflectivo) {
                            $fotosReflectivo = \App\Models\ReflectivoCotizacionFoto::whereIn('id', $validated['reflectivo_fotos_ids'])
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
                    }

                    // Llamar a endpoint de guardado de fotos
                    $request->merge([
                        'numero_pedido' => $pedido->numero_pedido,
                        'fotos' => $fotosData,
                        'telas' => $telasData,
                        'logos' => $logosData,
                    ]);

                    $resultadoFotos = $this->guardarFotosPedido($request);
                    $fotoResponse = json_decode($resultadoFotos->getContent(), true);

                    if (!$fotoResponse['success']) {
                        \Log::warning('⚠️ Error al guardar fotos, pero pedido ya creado', [
                            'error' => $fotoResponse['message'] ?? 'desconocido',
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('❌ Error guardando fotos', [
                        'error' => $e->getMessage(),
                    ]);
                    // No lanzar excepción, el pedido ya fue creado
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'pedido_id' => $pedido->id,
                'redirect' => route('asesores.pedidos-produccion.index')
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene próximo número de pedido
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

            \DB::beginTransaction();

            // Procesar por prenda
            $indexPrenda = 0;
            foreach ($prendas as $prenda) {
                // Guardar fotos de esta prenda
                if (!empty($fotos) && isset($fotos[$indexPrenda])) {
                    $fotosPreenda = $fotos[$indexPrenda];
                    if (is_array($fotosPreenda)) {
                        foreach ($fotosPreenda as $orden => $foto) {
                            \DB::table('prenda_fotos_pedido')->insert([
                                'prenda_pedido_id' => $prenda->id,
                                'ruta_original' => $foto['ruta_original'] ?? null,
                                'ruta_webp' => $foto['ruta_webp'] ?? null,
                                'ruta_miniatura' => $foto['ruta_miniatura'] ?? null,
                                'orden' => $orden + 1,
                                'ancho' => $foto['ancho'] ?? null,
                                'alto' => $foto['alto'] ?? null,
                                'tamaño' => $foto['tamaño'] ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                        \Log::info('✅ Fotos de prenda guardadas', [
                            'prenda_id' => $prenda->id,
                            'cantidad' => count($fotosPreenda),
                        ]);
                    }
                }

                // Guardar fotos de telas
                if (!empty($telas) && isset($telas[$indexPrenda])) {
                    $telasPreenda = $telas[$indexPrenda];
                    if (is_array($telasPreenda)) {
                        foreach ($telasPreenda as $tela) {
                            if (!empty($tela['fotos'])) {
                                foreach ($tela['fotos'] as $orden => $foto) {
                                    \DB::table('prenda_fotos_tela_pedido')->insert([
                                        'prenda_pedido_id' => $prenda->id,
                                        'tela_id' => $tela['tela_id'] ?? null,
                                        'color_id' => $tela['color_id'] ?? null,
                                        'ruta_original' => $foto['ruta_original'] ?? $foto['url'] ?? null,
                                        'ruta_webp' => $foto['ruta_webp'] ?? null,
                                        'ruta_miniatura' => $foto['ruta_miniatura'] ?? null,
                                        'orden' => $orden + 1,
                                        'ancho' => $foto['ancho'] ?? null,
                                        'alto' => $foto['alto'] ?? null,
                                        'tamaño' => $foto['tamaño'] ?? null,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            }
                        }
                    }
                }

                // Guardar logos/bordados
                if (!empty($logos) && isset($logos[$indexPrenda])) {
                    $logosPreenda = $logos[$indexPrenda];
                    if (is_array($logosPreenda)) {
                        foreach ($logosPreenda as $orden => $logo) {
                            \DB::table('prenda_fotos_logo_pedido')->insert([
                                'prenda_pedido_id' => $prenda->id,
                                'ruta_original' => $logo['ruta_original'] ?? $logo['url'] ?? null,
                                'ruta_webp' => $logo['ruta_webp'] ?? null,
                                'ruta_miniatura' => $logo['ruta_miniatura'] ?? null,
                                'orden' => $orden + 1,
                                'ubicacion' => $logo['ubicacion'] ?? null,
                                'ancho' => $logo['ancho'] ?? null,
                                'alto' => $logo['alto'] ?? null,
                                'tamaño' => $logo['tamaño'] ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                $indexPrenda++;
            }

            \DB::commit();

            \Log::info('✅ [guardarFotosPedido] Todas las fotos guardadas exitosamente');

            return response()->json([
                'success' => true,
                'message' => 'Fotos guardadas correctamente'
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
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

            return response()->json([
                'id' => $cotizacion->id,
                'numero' => $cotizacion->numero_cotizacion,
                'cliente' => $cotizacion->cliente,
                'asesora' => $cotizacion->asesora,
                'forma_pago' => $cotizacion->forma_pago ?? '',
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
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
