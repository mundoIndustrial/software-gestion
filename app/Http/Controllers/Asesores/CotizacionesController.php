<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCotizacionRequest;
use App\Models\Cotizacion;
use App\Services\ImagenCotizacionService;
use App\Services\CotizacionService;
use App\Services\PrendaService;
use App\Services\PedidoService;
use App\Services\FormatterService;
use App\Exceptions\CotizacionException;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exceptions\ImagenException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CotizacionesController extends Controller
{
    public function __construct(
        private CotizacionService $cotizacionService,
        private PrendaService $prendaService,
        private ImagenCotizacionService $imagenService,
        private PedidoService $pedidoService,
        private FormatterService $formatterService,
    ) {}

    /**
     * Mostrar lista de cotizaciones y borradores
     * 
     * Optimizado con eager loading y índices de base de datos
     */
    public function index()
    {
        \App\Services\QueryOptimizerService::iniciarAuditoria();

        // Obtener todos los registros con relaciones (sin paginar aún)
        $allCotizaciones = Cotizacion::where('user_id', Auth::id())
            ->where('es_borrador', false)
            ->with('tipoCotizacion', 'usuario', 'prendasCotizaciones', 'prendaCotizacion', 'logoCotizacion')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $allBorradores = Cotizacion::where('user_id', Auth::id())
            ->where('es_borrador', true)
            ->with('tipoCotizacion', 'usuario', 'prendasCotizaciones', 'prendaCotizacion', 'logoCotizacion')
            ->orderBy('created_at', 'desc')
            ->get();

        \Log::info('CotizacionesController@index - Datos obtenidos', [
            'user_id' => Auth::id(),
            'total_cotizaciones' => $allCotizaciones->count(),
            'total_borradores' => $allBorradores->count(),
        ]);

        // Filtrar por tipo usando obtenerTipoCotizacion()
        $cotPrendaCollection = $allCotizaciones->filter(fn($c) => $c->obtenerTipoCotizacion() === 'P');
        $cotLogoCollection = $allCotizaciones->filter(fn($c) => $c->obtenerTipoCotizacion() === 'B');
        $cotPBCollection = $allCotizaciones->filter(fn($c) => $c->obtenerTipoCotizacion() === 'PB');
        
        $borPrendaCollection = $allBorradores->filter(fn($c) => $c->obtenerTipoCotizacion() === 'P');
        $borLogoCollection = $allBorradores->filter(fn($c) => $c->obtenerTipoCotizacion() === 'B');
        $borPBCollection = $allBorradores->filter(fn($c) => $c->obtenerTipoCotizacion() === 'PB');

        // Debug: mostrar tipos de cada cotización
        $tiposDebug = [];
        foreach ($allCotizaciones as $cot) {
            $tipo = $cot->obtenerTipoCotizacion();
            $tiposDebug[] = [
                'id' => $cot->id,
                'tipo_retornado' => $tipo,
                'tipo_cotizacion_id' => $cot->tipo_cotizacion_id,
                'tiene_prendas' => $cot->prendasCotizaciones()->count() > 0,
                'tiene_logo' => $cot->logoCotizacion ? true : false,
                'tiene_prenda_cot' => $cot->prendaCotizacion ? true : false,
            ];
        }
        \Log::info('CotizacionesController@index - Debug tipos', $tiposDebug);

        \Log::info('CotizacionesController@index - Filtros aplicados', [
            'prenda' => $cotPrendaCollection->count(),
            'logo' => $cotLogoCollection->count(),
            'prenda_bordado' => $cotPBCollection->count(),
            'bor_prenda' => $borPrendaCollection->count(),
            'bor_logo' => $borLogoCollection->count(),
            'bor_pb' => $borPBCollection->count(),
        ]);

        // Convertir a paginadores manuales
        $page = request()->get('page', 1);
        $perPage = 15;
        
        $cotizacionesPrenda = $this->paginateCollection($cotPrendaCollection, $perPage, $page, 'page_cot_prenda');
        \Log::debug('paginateCollection - page_cot_prenda OK');
        
        $cotizacionesLogo = $this->paginateCollection($cotLogoCollection, $perPage, $page, 'page_cot_logo');
        \Log::debug('paginateCollection - page_cot_logo OK');
        
        $cotizacionesPrendaBordado = $this->paginateCollection($cotPBCollection, $perPage, $page, 'page_cot_pb');
        \Log::debug('paginateCollection - page_cot_pb OK');
        
        $borradorespPrenda = $this->paginateCollection($borPrendaCollection, $perPage, $page, 'page_bor_prenda');
        \Log::debug('paginateCollection - page_bor_prenda OK');
        
        $borradoresLogo = $this->paginateCollection($borLogoCollection, $perPage, $page, 'page_bor_logo');
        \Log::debug('paginateCollection - page_bor_logo OK');
        
        $borradorespPrendaBordado = $this->paginateCollection($borPBCollection, $perPage, $page, 'page_bor_pb');
        \Log::debug('paginateCollection - page_bor_pb OK');

        // Colecciones combinadas con paginación
        \Log::debug('ANTES DE MERGE - cotPrendaCollection count: ' . $cotPrendaCollection->count());
        \Log::debug('ANTES DE MERGE - cotLogoCollection count: ' . $cotLogoCollection->count());
        \Log::debug('ANTES DE MERGE - cotPBCollection count: ' . $cotPBCollection->count());
        
        $allCotizacionesCollection = $cotPrendaCollection->merge($cotLogoCollection)->merge($cotPBCollection)->sortByDesc('created_at')->values();
        \Log::debug('DESPUES DE MERGE - allCotizacionesCollection count: ' . $allCotizacionesCollection->count());
        
        $allBorradoresCollection = $borPrendaCollection->merge($borLogoCollection)->merge($borPBCollection)->sortByDesc('created_at')->values();
        
        \Log::info('CotizacionesController@index - Colecciones combinadas', [
            'total_cot' => $allCotizacionesCollection->count(),
            'total_bor' => $allBorradoresCollection->count(),
            'cot_prenda_count' => $cotPrendaCollection->count(),
            'cot_logo_count' => $cotLogoCollection->count(),
            'cot_pb_count' => $cotPBCollection->count(),
            'merged_before_sort' => $cotPrendaCollection->merge($cotLogoCollection)->merge($cotPBCollection)->count(),
        ]);

        // Debug: mostrar tipos en la colección combinada
        $tiposCombinados = [];
        foreach ($allCotizacionesCollection as $cot) {
            $tiposCombinados[] = [
                'id' => $cot->id,
                'tipo' => $cot->obtenerTipoCotizacion(),
            ];
        }
        \Log::info('CotizacionesController@index - Tipos en colección combinada', $tiposCombinados);
        
        $cotizacionesTodas = $this->paginateCollection($allCotizacionesCollection, $perPage, $page, 'page_cot_todas');
        $borradoresTodas = $this->paginateCollection($allBorradoresCollection, $perPage, $page, 'page_bor_todas');

        \Log::info('CotizacionesController@index - Paginadores creados', [
            'cot_todas_total' => $cotizacionesTodas->total(),
            'cot_todas_current_page' => $cotizacionesTodas->currentPage(),
            'cot_todas_count' => $cotizacionesTodas->count(),
            'bor_todas_total' => $borradoresTodas->total(),
        ]);

        // Colecciones sin paginar para compatibilidad
        $cotizaciones = $allCotizacionesCollection;
        $borradores = $allBorradoresCollection;

        \App\Services\QueryOptimizerService::finalizarYReportar('CotizacionesController@index');

        return view('asesores.cotizaciones.index', compact(
            'cotizacionesPrenda', 'cotizacionesLogo', 'cotizacionesPrendaBordado',
            'cotizacionesTodas', 'borradoresTodas',
            'borradorespPrenda', 'borradoresLogo', 'borradorespPrendaBordado',
            'cotizaciones', 'borradores'
        ))->with([
            'pageNameCotTodas' => 'page_cot_todas',
            'pageNameCotPrenda' => 'page_cot_prenda',
            'pageNameCotLogo' => 'page_cot_logo',
            'pageNameCotPB' => 'page_cot_pb',
            'pageNameBorTodas' => 'page_bor_todas',
            'pageNameBorPrenda' => 'page_bor_prenda',
            'pageNameBorLogo' => 'page_bor_logo',
            'pageNameBorPB' => 'page_bor_pb',
        ]);
    }

    /**
     * Convertir una colección filtrada a un paginador
     */
    private function paginateCollection($collection, $perPage = 15, $page = 1, $pageName = 'page')
    {
        // Obtener la página actual del query parameter específico
        $currentPage = (int) request()->query($pageName, 1);
        $currentPage = max(1, $currentPage);
        
        // Re-indexar la colección para asegurar que forPage funcione correctamente
        $collection = $collection->values();
        
        // Usar el método forPage de Collection que es lo correcto para esto
        $items = $collection->forPage($currentPage, $perPage);
        
        // Construir query parameters limpios
        $query = [];
        foreach (request()->query() as $key => $value) {
            // Excluir todos los parámetros de paginación excepto el nuestro
            if (strpos($key, 'page_') === 0 && $key !== $pageName) {
                continue;
            }
            $query[$key] = $value;
        }
        
        // Obtener la URL base sin query parameters
        $path = request()->url();
        
        // Crear el paginador con el pageName correcto
        $paginator = new LengthAwarePaginator(
            $items->all(), // Convertir a array
            $collection->count(),
            $perPage,
            $currentPage,
            [
                'path' => $path,
                'query' => $query,
                'fragment' => null,
                'pageName' => $pageName,
            ]
        );
        
        // Asegurar que el pageName se use correctamente
        $paginator->setPageName($pageName);
        
        \Log::debug("paginateCollection - {$pageName}", [
            'pageName' => $pageName,
            'currentPage' => $currentPage,
            'collection_count' => $collection->count(),
            'items_in_page' => count($items),
            'query_param' => request()->query($pageName),
            'path' => $path,
            'query_keys' => array_keys($query),
            'paginator_pageName' => $paginator->getPageName(),
        ]);
        
        return $paginator;
    }

    /**
     * Guardar cotización o borrador (nueva o actualización)
     * 
     * Delega completamente a los servicios:
     * - FormatterService: procesa inputs
     * - CotizacionService: crea/actualiza cotización
     * - PrendaService: crea prendas
     * - ImagenCotizacionService: procesa imágenes
     * 
     * Las excepciones se manejan centralmente en Handler.php
     * 
     * @param StoreCotizacionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function guardar(StoreCotizacionRequest $request)
    {
        try {
            \Log::info('Iniciando guardar cotización', [
                'request_headers' => $request->headers->all(),
                'request_method' => $request->method(),
                'request_wants_json' => $request->wantsJson()
            ]);
            
            $validado = $request->validated();
            
            \Log::info('Datos validados en guardar', [
                'keys' => array_keys($validado),
                'tipo' => $validado['tipo'] ?? null,
                'cliente' => $validado['cliente'] ?? null
            ]);
            
            // Procesar inputs usando FormatterService
            $datosFormulario = $this->formatterService->procesarInputsFormulario($validado);
            
            \Log::info('Datos procesados por FormatterService', [
                'keys' => array_keys($datosFormulario),
                'especificaciones_presente' => !empty($datosFormulario['especificaciones']),
                'especificaciones_count' => count($datosFormulario['especificaciones'] ?? []),
                'especificaciones_keys' => array_keys($datosFormulario['especificaciones'] ?? [])
            ]);
            
            $tipo = $validado['tipo'] ?? 'borrador';
            $cotizacionId = $validado['cotizacion_id'] ?? null;
            
            // ACTUALIZAR: Si existe ID
            if ($cotizacionId) {
                \Log::info('Actualizando cotización existente', ['id' => $cotizacionId]);
                
                $cotizacion = Cotizacion::findOrFail($cotizacionId);
                $this->validarAutorizacionCotizacion($cotizacion);
                $this->validarEsBorrador($cotizacion);
                
                $this->cotizacionService->actualizarBorrador($cotizacion, $datosFormulario);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Cotización actualizada correctamente',
                    'cotizacion_id' => $cotizacion->id
                ]);
            }

            \Log::info('Creando nueva cotización', [
                'tipo' => $tipo,
                'cliente' => $datosFormulario['cliente'] ?? null
            ]);

            // CREAR: Nueva cotización
            $cotizacion = $this->cotizacionService->crear(
                $datosFormulario,
                $tipo,
                $datosFormulario['tipo_cotizacion']
            );
            
            \Log::info('Cotización creada', ['id' => $cotizacion->id]);
            
            // Crear prendas
            if (!empty($datosFormulario['productos'])) {
                \Log::info('Creando prendas', ['cantidad' => count($datosFormulario['productos'])]);
                $this->prendaService->crearPrendasCotizacion($cotizacion, $datosFormulario['productos']);
            }
            
            \Log::info('Creando logo/LOGO');
            
            // Crear logo/LOGO
            $this->cotizacionService->crearLogoCotizacion($cotizacion, $datosFormulario);
            
            \Log::info('Cotización completada', ['id' => $cotizacion->id, 'tipo' => $tipo]);
            
            $redirect = ($tipo === 'borrador')
                ? route('asesores.cotizaciones.index', ['page' => 'page_bor_prenda'])
                : route('asesores.cotizaciones.index', ['page' => 'page_cot_prenda']);
            
            return response()->json([
                'success' => true,
                'message' => ($tipo === 'borrador') 
                    ? 'Cotización guardada en borradores' 
                    : 'Cotización enviada correctamente',
                'cotizacion_id' => $cotizacion->id,
                'redirect' => $redirect
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            \Log::error('Error de validación en guardar cotización', [
                'errors' => $ve->errors(),
                'file' => $ve->getFile(),
                'line' => $ve->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'validation_errors' => $ve->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en guardar cotización', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'class' => get_class($e)
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la cotización: ' . $e->getMessage(),
                'error_class' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Endpoint de prueba sin FormRequest para diagnosticar problemas
     */
    public function guardarTest(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Endpoint de prueba funcionando',
            'received_data' => [
                'tipo' => $request->input('tipo'),
                'cliente' => $request->input('cliente'),
                'productos_count' => count($request->input('productos', [])),
                'headers' => ['Content-Type' => $request->header('Content-Type')],
                'wants_json' => $request->wantsJson(),
                'all_keys' => array_keys($request->all())
            ]
        ]);
    }

    /**
     * Ver detalle de cotización con eager loading optimizado
     */
    public function show(int $id)
    {
        \App\Services\QueryOptimizerService::iniciarAuditoria();

        // Eager loading completo de relaciones necesarias
        $cotizacion = Cotizacion::with([
            'usuario',
            'tipoCotizacion',
            'prendasCotizaciones.variantes.color',
            'prendasCotizaciones.variantes.tela',
            'prendasCotizaciones.variantes.tipoManga',
            'prendasCotizaciones.variantes.tipoBroche',
            'logoCotizacion'
        ])->findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id()) {
            throw new CotizacionException(
                'No tienes autorización para ver esta cotización',
                CotizacionException::UNAUTHORIZED,
                ['cotizacion_id' => $id]
            );
        }

        // Obtener datos de logo/LOGO (ya cargado con eager loading)
        $logo = $cotizacion->logoCotizacion;

        // Si es una petición AJAX, retornar JSON
        if (request()->wantsJson()) {
            $prendas = $cotizacion->prendasCotizaciones ?? collect();
            
            // Extraer forma de pago del array de especificaciones
            $formaPago = '';
            $especificaciones = $cotizacion->especificaciones;
            
            // Si es string JSON, decodificar
            if (is_string($especificaciones)) {
                $especificaciones = json_decode($especificaciones, true) ?? [];
            }
            
            \Log::debug('Extrayendo forma_pago', [
                'especificaciones_type' => gettype($especificaciones),
                'especificaciones' => $especificaciones
            ]);
            
            if (is_array($especificaciones)) {
                $formaPagoArray = $especificaciones['forma_pago'] ?? null;
                \Log::debug('forma_pago encontrado', [
                    'formaPagoArray_type' => gettype($formaPagoArray),
                    'formaPagoArray' => $formaPagoArray
                ]);
                
                if (is_array($formaPagoArray) && !empty($formaPagoArray)) {
                    $formaPago = $formaPagoArray[0];
                } elseif (is_string($formaPagoArray)) {
                    $formaPago = $formaPagoArray;
                }
            }
            
            \Log::debug('forma_pago final', ['formaPago' => $formaPago]);
            
            $respuesta = response()->json([
                'id' => $cotizacion->id,
                'cliente' => $cotizacion->cliente,
                'asesora' => $cotizacion->asesora,
                'forma_pago' => $formaPago,
                'prendas' => $prendas->map(function($prenda) {
                    $variante = $prenda->variantes?->first();
                    
                    return [
                        'id' => $prenda->id,
                        'nombre_producto' => $prenda->nombre_producto,
                        'descripcion' => $prenda->descripcion,
                        'tallas' => $prenda->tallas ?? [],
                        'fotos' => $prenda->fotos ?? [],
                        'telas' => $prenda->telas ?? [],
                        // Variaciones
                        'variantes' => [
                            'color' => $variante?->color?->nombre ?? null,
                            'tela' => $variante?->tela?->nombre ?? null,
                            'tela_referencia' => $variante?->tela?->referencia ?? null,
                            'manga' => $variante?->tipoManga?->nombre ?? null,
                            'broche' => $variante?->tipoBroche?->nombre ?? null,
                            'tiene_bolsillos' => $variante?->tiene_bolsillos ?? false,
                            'tiene_reflectivo' => $variante?->tiene_reflectivo ?? false,
                            'observaciones' => $variante?->descripcion_adicional ?? null
                        ]
                    ];
                })
            ]);

            \App\Services\QueryOptimizerService::finalizarYReportar('CotizacionesController@show (JSON)');
            return $respuesta;
        }

        // Pasar logo null-safe a vista
        \App\Services\QueryOptimizerService::finalizarYReportar('CotizacionesController@show (HTML)');
        return view('asesores.cotizaciones.show', compact('cotizacion', 'logo'));
    }

    /**
     * Editar borrador
     */
    public function editarBorrador(int $id)
    {
        $cotizacion = Cotizacion::with([
            'logoCotizacion'
        ])->findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id() || !$cotizacion->es_borrador) {
            throw new CotizacionException(
                'No tienes autorización para editar este borrador',
                CotizacionException::UNAUTHORIZED,
                ['cotizacion_id' => $id]
            );
        }

        return view('asesores.pedidos.create-friendly', [
            'cotizacion' => $cotizacion,
            'esEdicion' => true
        ]);
    }

    /**
     * Subir imágenes a una cotización
     * 
     * Delega completamente a ImagenCotizacionService:
     * - Procesamiento de imágenes (WebP/GD)
     * - Almacenamiento en Storage
     * - Actualización de prendas_cotizaciones/logo_cotizaciones
     * 
     * Las excepciones se manejan centralmente en Handler.php
     */
    public function subirImagenes(Request $request, int $id)
    {
        $this->validarAutorizacionCotizacion(
            $cotizacion = Cotizacion::findOrFail($id)
        );

        $request->validate([
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'tipo' => 'required|in:bordado,estampado,tela,prenda,logo'
        ]);

        $tipo = $request->input('tipo');
        $archivos = $request->file('imagenes', []);

        if (empty($archivos)) {
            throw new ImagenException(
                'No hay imágenes para subir',
                ImagenException::FILE_NOT_FOUND
            );
        }

        // Procesar todas las imágenes con el servicio
        $rutasGuardadas = $this->imagenService->guardarMultiples($id, $archivos, $tipo);

        if (empty($rutasGuardadas)) {
            throw new ImagenException(
                'Error al procesar las imágenes',
                ImagenException::CONVERSION_ERROR
            );
        }

        // Actualizar referencias en modelos
        $this->actualizarReferenciasPrendas($cotizacion, $rutasGuardadas, $tipo, $request);

        return response()->json([
            'success' => true,
            'message' => count($rutasGuardadas) . " imágenes de tipo '{$tipo}' guardadas",
            'rutas' => $rutasGuardadas
        ]);
    }

    /**
     * Actualizar referencias de imágenes en prendas y logo
     * 
     * @param Cotizacion $cotizacion
     * @param array $rutas
     * @param string $tipo
     * @param Request $request
     */
    private function actualizarReferenciasPrendas(Cotizacion $cotizacion, array $rutas, string $tipo, Request $request): void
    {
        if ($tipo === 'prenda' || $tipo === 'tela') {
            $prendas = $cotizacion->prendasCotizaciones;
            
            // Verificar que prendasCotizaciones no sea null
            if (!$prendas) {
                \Log::warning('prendasCotizaciones es null', [
                    'cotizacion_id' => $cotizacion->id
                ]);
                return;
            }
            
            $prendaIndexes = $request->input('prendaIndex', []);
            
            // Agrupar rutas por índice de prenda
            $arregloPorPrenda = [];
            foreach ($rutas as $index => $ruta) {
                $prendaIndex = isset($prendaIndexes[$index]) ? intval($prendaIndexes[$index]) : $index;
                if (!isset($arregloPorPrenda[$prendaIndex])) {
                    $arregloPorPrenda[$prendaIndex] = [];
                }
                $arregloPorPrenda[$prendaIndex][] = $ruta;
            }
            
            // Actualizar cada prenda
            foreach ($arregloPorPrenda as $prendaIndex => $rutasPrenda) {
                if (isset($prendas[$prendaIndex])) {
                    $prenda = $prendas[$prendaIndex];
                    $campo = ($tipo === 'prenda') ? 'fotos' : 'telas';
                    
                    // Null-safe property access
                    $valoresActuales = [];
                    if (property_exists($prenda, $campo)) {
                        $valoresActuales = $prenda->$campo ?? [];
                    }
                    
                    if (!is_array($valoresActuales)) {
                        $valoresActuales = [];
                    }
                    
                    $prenda->update([$campo => array_merge($valoresActuales, $rutasPrenda)]);
                }
            }
        } elseif ($tipo === 'logo') {
            $logo = $cotizacion->logoCotizacion;
            // Null-safe logo access
            if ($logo) {
                $imagenes = $logo->imagenes ?? [];
                if (!is_array($imagenes)) {
                    $imagenes = [];
                }
                $logo->update(['imagenes' => array_merge($imagenes, $rutas)]);
            } else {
                \Log::info('Logo no encontrado para cotización', [
                    'cotizacion_id' => $cotizacion->id
                ]);
            }
        }
    }

    /**
     * Eliminar cotización (solo si es borrador)
     * 
     * Las excepciones se manejan centralmente en Handler.php
     */
    public function destroy(int $id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id()) {
            throw new CotizacionException(
                'No tienes autorización para eliminar esta cotización',
                CotizacionException::UNAUTHORIZED,
                ['cotizacion_id' => $id]
            );
        }

        // Solo permitir eliminar si es borrador
        if (!$cotizacion->es_borrador) {
            throw new CotizacionException(
                'No se pueden eliminar cotizaciones enviadas. Solo se pueden eliminar borradores.',
                CotizacionException::INVALID_STATE,
                ['cotizacion_id' => $id]
            );
        }

        // Usar servicio para eliminar con transacción
        $this->cotizacionService->eliminar($cotizacion);

        return response()->json([
            'success' => true,
            'message' => 'Borrador eliminado completamente incluyendo todas las imágenes y datos asociados'
        ]);
    }

    /**
     * Cambiar estado de cotización (borrador → enviada, enviada → aceptada, etc.)
     * 
     * Las excepciones se manejan centralmente en Handler.php
     */
    public function cambiarEstado(int $id, string $estado)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        
        if ($cotizacion->user_id !== Auth::id()) {
            throw new CotizacionException(
                'No tienes autorización para cambiar el estado de esta cotización',
                CotizacionException::UNAUTHORIZED,
                ['cotizacion_id' => $id]
            );
        }

        // Usar servicio para cambiar estado
        $this->cotizacionService->cambiarEstado($cotizacion, $estado);
        
        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente'
        ]);
    }

    /**
     * Aceptar cotización y crear pedido de producción
     * 
     * Delega completamente a PedidoService
     * Las excepciones se manejan centralmente en Handler.php
     */
    public function aceptarCotizacion(int $id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        $this->validarAutorizacionCotizacion($cotizacion);

        $pedido = $this->pedidoService->aceptarCotizacion($cotizacion);
        
        return response()->json([
            'success' => true,
            'message' => 'Cotización aceptada y pedido creado',
            'pedido_id' => $pedido->id
        ]);
    }


    /**
     * Validar que la cotización sea borrador
     */
    private function validarEsBorrador(Cotizacion $cotizacion): void
    {
        if (!$cotizacion->es_borrador) {
            throw new CotizacionException(
                'No se pueden actualizar cotizaciones enviadas',
                CotizacionException::INVALID_STATE,
                ['cotizacion_id' => $cotizacion->id, 'estado' => $cotizacion->estado]
            );
        }
    }

    /**
     * Validar que el usuario sea propietario de la cotización
     */
    private function validarAutorizacionCotizacion(Cotizacion $cotizacion): void
    {
        if ($cotizacion->user_id !== Auth::id()) {
            throw new CotizacionException(
                'No tienes autorización para acceder a esta cotización',
                CotizacionException::UNAUTHORIZED,
                ['cotizacion_id' => $cotizacion->id, 'user_id' => Auth::id()]
            );
        }
    }

    /**
     * Eliminar una imagen específica de una cotización
     * 
     * @param Request $request
     * @param int $id ID de la cotización
     * @return \Illuminate\Http\JsonResponse
     */
    public function eliminarImagen(Request $request, int $id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        
        $this->validarAutorizacionCotizacion($cotizacion);

        $request->validate([
            'ruta' => 'required|string'
        ]);

        $ruta = $request->input('ruta');
        
        try {
            // Eliminar de Storage
            $rutaRelativa = str_replace('/storage/', '', $ruta);
            if (Storage::disk('public')->exists($rutaRelativa)) {
                Storage::disk('public')->delete($rutaRelativa);
                \Log::info('Imagen eliminada de storage', ['ruta' => $rutaRelativa]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar imagen', [
                'cotizacion_id' => $id,
                'ruta' => $ruta,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener valores únicos para filtros
     * Devuelve los valores únicos de cada columna para los dropdowns de filtro
     */
    public function obtenerValoresFiltro()
    {
        $userId = Auth::id();

        // Obtener valores únicos de cada columna
        $fechas = Cotizacion::where('user_id', $userId)
            ->where('es_borrador', false)
            ->distinct()
            ->orderBy('created_at', 'desc')
            ->pluck('created_at')
            ->map(fn($date) => $date->format('d/m/Y'))
            ->unique()
            ->values();

        $codigos = Cotizacion::where('user_id', $userId)
            ->where('es_borrador', false)
            ->distinct()
            ->whereNotNull('numero_cotizacion')
            ->pluck('numero_cotizacion')
            ->unique()
            ->sort()
            ->values();

        $clientes = Cotizacion::where('user_id', $userId)
            ->where('es_borrador', false)
            ->distinct()
            ->whereNotNull('cliente')
            ->pluck('cliente')
            ->unique()
            ->sort()
            ->values();

        $tipos = Cotizacion::where('user_id', $userId)
            ->where('es_borrador', false)
            ->distinct()
            ->get()
            ->map(fn($cot) => $cot->obtenerTipoCotizacion())
            ->map(fn($tipo) => match($tipo) {
                'P' => 'Prenda',
                'B' => 'Logo',
                'PB' => 'Prenda/Bordado',
                default => $tipo
            })
            ->unique()
            ->sort()
            ->values();

        $estados = Cotizacion::where('user_id', $userId)
            ->where('es_borrador', false)
            ->distinct()
            ->pluck('estado')
            ->unique()
            ->map(fn($estado) => ucfirst($estado))
            ->sort()
            ->values();

        return response()->json([
            'fechas' => $fechas,
            'codigos' => $codigos,
            'clientes' => $clientes,
            'tipos' => $tipos,
            'estados' => $estados
        ]);
    }
}

