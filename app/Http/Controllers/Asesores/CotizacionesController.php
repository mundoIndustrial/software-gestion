<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCotizacionRequest;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaCotizacionFriendly;
use App\Models\ProcesoPrenda;
use App\Models\VariantePrenda;
use App\Models\TipoPrenda;
use App\Services\ImagenCotizacionService;
use App\Services\CotizacionService;
use App\Services\PrendaService;
use App\Services\PedidoService;
use App\Services\FormatterService;
use App\DTOs\CotizacionDTO;
use App\Exceptions\CotizacionException;
use App\Exceptions\PrendaException;
use App\Exceptions\ImagenException;
use App\Exceptions\PedidoException;
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

        // Query 1: Cotizaciones enviadas (con eager loading)
        $cotizaciones = Cotizacion::where('user_id', Auth::id())
            ->where('es_borrador', false)
            ->with('tipoCotizacion', 'usuario')  // Eager load relaciones comunes
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        // Query 2: Borradores (con eager loading)
        $borradores = Cotizacion::where('user_id', Auth::id())
            ->where('es_borrador', true)
            ->with('tipoCotizacion', 'usuario')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        \App\Services\QueryOptimizerService::finalizarYReportar('CotizacionesController@index');

        return view('asesores.cotizaciones.index', compact('cotizaciones', 'borradores'));
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
                'keys' => array_keys($datosFormulario)
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
            
            \Log::info('Creando logo/bordado/estampado');
            
            // Crear logo/bordado/estampado
            $this->cotizacionService->crearLogoCotizacion($cotizacion, $datosFormulario);
            
            \Log::info('Cotización completada', ['id' => $cotizacion->id, 'tipo' => $tipo]);
            
            return response()->json([
                'success' => true,
                'message' => ($tipo === 'borrador') 
                    ? 'Cotización guardada en borradores' 
                    : 'Cotización enviada correctamente',
                'cotizacion_id' => $cotizacion->id
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
    public function show($id)
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

        // Obtener datos de logo/bordado/estampado (ya cargado con eager loading)
        $logo = $cotizacion->logoCotizacion;

        // Si es una petición AJAX, retornar JSON
        if (request()->wantsJson()) {
            $prendas = $cotizacion->prendasCotizaciones ?? collect();
            
            $respuesta = response()->json([
                'id' => $cotizacion->id,
                'cliente' => $cotizacion->cliente,
                'asesora' => $cotizacion->asesora,
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
    public function editarBorrador($id)
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
    public function subirImagenes(Request $request, $id)
    {
        $this->validarAutorizacionCotizacion(
            $cotizacion = Cotizacion::findOrFail($id)
        );

        $request->validate([
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'tipo' => 'required|in:bordado,estampado,tela,prenda,general'
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
        } elseif ($tipo === 'general') {
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
    public function destroy($id)
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
    public function cambiarEstado($id, $estado)
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
    public function aceptarCotizacion($id)
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
     * @param $id ID de la cotización
     * @return \Illuminate\Http\JsonResponse
     */
    public function eliminarImagen(Request $request, $id)
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
}

