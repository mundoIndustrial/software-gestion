<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Pedidos\Services\PrendaEditorService;
use App\Application\Pedidos\DTOs\PrendaEditadaDTO;
use App\Domain\Pedidos\Services\TallaProcessorService;
use App\Domain\Pedidos\Services\VariacionProcessorService;
use App\Domain\Pedidos\Services\ProcesoProcessorService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Infrastructure Controller: PrendaEditorController
 * 
 * Expone los endpoints HTTP para la edición de prendas
 * Actúa como adaptador entre HTTP y los servicios de aplicación
 */
class PrendaEditorController extends Controller
{
    private PrendaEditorService $prendaEditorService;
    private TallaProcessorService $tallaProcessor;
    private VariacionProcessorService $variacionProcessor;
    private ProcesoProcessorService $procesoProcessor;
    
    public function __construct(
        PrendaEditorService $prendaEditorService, 
        TallaProcessorService $tallaProcessor,
        VariacionProcessorService $variacionProcessor,
        ProcesoProcessorService $procesoProcessor
    ) {
        $this->prendaEditorService = $prendaEditorService;
        $this->tallaProcessor = $tallaProcessor;
        $this->variacionProcessor = $variacionProcessor;
        $this->procesoProcessor = $procesoProcessor;
    }
    
    /**
     * Obtiene los datos de una prenda para edición
     * 
     * GET /api/prendas/{id}/editar
     */
    public function editar(Request $request, int $id): JsonResponse
    {
        try {
            $cotizacionId = $request->get('cotizacion_id');
            
            Log::info('[PrendaEditorController] Obteniendo datos para edición', [
                'prenda_id' => $id,
                'cotizacion_id' => $cotizacionId,
                'user_id' => auth()->id()
            ]);
            
            $prendaDTO = $this->prendaEditorService->obtenerDatosEdicion($id, $cotizacionId);
            
            Log::info('[PrendaEditorController] Datos obtenidos exitosamente', [
                'prenda_id' => $id,
                'resumen' => $prendaDTO->getResumen()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $prendaDTO->toArray()
            ]);
            
        } catch (\Exception $e) {
            Log::error('[PrendaEditorController] Error obteniendo datos de edición', [
                'prenda_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de la prenda: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Prepara los datos para guardar cambios
     * 
     * POST /api/prendas/preparar-guardar
     */
    public function prepararGuardar(Request $request): JsonResponse
    {
        try {
            $datos = $request->all();
            
            Log::info('[PrendaEditorController] Preparando datos para guardar', [
                'datos_recibidos' => array_keys($datos),
                'user_id' => auth()->id()
            ]);
            
            $datosProcesados = $this->prendaEditorService->prepararParaGuardar($datos);
            
            Log::info('[PrendaEditorController] Datos preparados exitosamente', [
                'campos_procesados' => array_keys($datosProcesados)
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $datosProcesados
            ]);
            
        } catch (\Exception $e) {
            Log::error('[PrendaEditorController] Error preparando datos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error preparando datos: ' . $e->getMessage()
            ], 422);
        }
    }
    
    /**
     * Valida una prenda completa
     * 
     * POST /api/prendas/validar
     */
    public function validar(Request $request): JsonResponse
    {
        try {
            $datos = $request->all();
            
            Log::info('[PrendaEditorController] Validando prenda', [
                'campos' => array_keys($datos),
                'user_id' => auth()->id()
            ]);
            
            $errores = $this->prendaEditorService->validarPrendaCompleta($datos);
            
            $esValida = empty($errores);
            
            Log::info('[PrendaEditorController] Validación completada', [
                'es_valida' => $esValida,
                'cantidad_errores' => count($errores),
                'errores' => $errores
            ]);
            
            return response()->json([
                'success' => true,
                'valid' => $esValida,
                'errors' => $errores
            ]);
            
        } catch (\Exception $e) {
            Log::error('[PrendaEditorController] Error en validación', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error en validación: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtiene tipos de manga disponibles
     * 
     * GET /api/prendas/tipos-manga
     */
    public function tiposManga(): JsonResponse
    {
        try {
            Log::info('[PrendaEditorController] Obteniendo tipos de manga', [
                'user_id' => auth()->id()
            ]);
            
            $tiposManga = $this->prendaEditorService->obtenerTiposMangaDisponibles();
            
            Log::info('[PrendaEditorController] Tipos de manga obtenidos', [
                'cantidad' => count($tiposManga)
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $tiposManga
            ]);
            
        } catch (\Exception $e) {
            Log::error('[PrendaEditorController] Error obteniendo tipos de manga', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo tipos de manga: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtiene datos de cotización para prendas Reflectivo/Logo
     * 
     * GET /api/cotizaciones/{cotizacionId}/prendas/{prendaId}/datos-cotizacion
     */
    public function datosCotizacion(Request $request, int $cotizacionId, int $prendaId): JsonResponse
    {
        try {
            Log::info('[PrendaEditorController] Obteniendo datos de cotización', [
                'cotizacion_id' => $cotizacionId,
                'prenda_id' => $prendaId,
                'user_id' => auth()->id()
            ]);
            
            // Obtener datos de prenda para tener contexto
            $prendaDTO = $this->prendaEditorService->obtenerDatosEdicion($prendaId, $cotizacionId);
            
            // Si es Reflectivo/Logo, cargar datos adicionales
            $datosAdicionales = [];
            if ($prendaDTO->esPrendaCotizacion()) {
                // Aquí iría la lógica específica para cargar datos de cotización
                // Por ahora retornamos estructura básica
                $datosAdicionales = [
                    'telas' => $prendaDTO->telas(),
                    'variaciones' => $prendaDTO->variantes(),
                    'ubicaciones' => [],
                    'descripcion' => $prendaDTO->descripcion()
                ];
            }
            
            Log::info('[PrendaEditorController] Datos de cotización obtenidos', [
                'cotizacion_id' => $cotizacionId,
                'prenda_id' => $prendaId,
                'tiene_datos_adicionales' => !empty($datosAdicionales)
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $datosAdicionales
            ]);
            
        } catch (\Exception $e) {
            Log::error('[PrendaEditorController] Error obteniendo datos de cotización', [
                'cotizacion_id' => $cotizacionId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo datos de cotización: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Endpoint de debug para verificar estructura de datos
     * 
     * GET /api/prendas/debug/{id}
     */
    public function debug(Request $request, int $id): JsonResponse
    {
        try {
            $cotizacionId = $request->get('cotizacion_id');
            
            Log::info('[PrendaEditorController] Debug - Analizando estructura', [
                'prenda_id' => $id,
                'cotizacion_id' => $cotizacionId
            ]);
            
            $prendaDTO = $this->prendaEditorService->obtenerDatosEdicion($id, $cotizacionId);
            
            $debugInfo = [
                'prenda_id' => $id,
                'cotizacion_id' => $cotizacionId,
                'resumen' => $prendaDTO->getResumen(),
                'estructura_completa' => $prendaDTO->toArray(),
                'analisis_telas' => [
                    'total' => $prendaDTO->cantidadTelas(),
                    'con_referencia' => $prendaDTO->cantidadTelas() - count($prendaDTO->telasSinReferencia()),
                    'sin_referencia' => count($prendaDTO->telasSinReferencia()),
                    'con_imagenes' => count($prendaDTO->telasConImagenes())
                ],
                'analisis_imagenes' => [
                    'total' => $prendaDTO->cantidadImagenes(),
                    'tipos' => array_unique(array_map(function($img) {
                        return isset($img['urlDesdeDB']) && $img['urlDesdeDB'] ? 'BD' : 'Formulario';
                    }, $prendaDTO->imagenes()))
                ]
            ];
            
            return response()->json([
                'success' => true,
                'debug' => $debugInfo
            ]);
            
        } catch (\Exception $e) {
            Log::error('[PrendaEditorController] Error en debug', [
                'prenda_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error en debug: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Procesa tallas de una prenda usando el servicio DDD
     * 
     * POST /api/prendas/{id}/procesar-tallas
     */
    public function procesarTallas(Request $request, int $id): JsonResponse
    {
        try {
            Log::info('[PrendaEditorController] Procesando tallas', [
                'prenda_id' => $id,
                'user_id' => auth()->id()
            ]);
            
            // Obtener datos de la prenda
            $prenda = $this->prendaEditorService->obtenerPrenda($id);
            
            // Procesar tallas con el servicio DDD
            $tallasDTO = $this->tallaProcessor->procesarTallasPrenda([
                'cantidad_talla' => $prenda->cantidad_talla,
                'tallas' => $prenda->tallas,
                'variantes' => $prenda->variantes,
                'procesos' => $prenda->procesos,
                'cantidad' => $prenda->cantidad,
                'genero' => $prenda->genero
            ]);
            
            Log::info('[PrendaEditorController] Tallas procesadas exitosamente', [
                'prenda_id' => $id,
                'genero_principal' => $tallasDTO->genero_principal,
                'tipo_talla' => $tallasDTO->tipo_talla,
                'total_general' => $tallasDTO->total_general
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $tallasDTO->toArray()
            ]);
            
        } catch (\Exception $e) {
            Log::error('[PrendaEditorController] Error procesando tallas', [
                'prenda_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error procesando tallas: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Procesa variaciones de una prenda usando el servicio DDD
     * 
     * POST /api/prendas/{id}/procesar-variaciones
     */
    public function procesarVariaciones(Request $request, int $id): JsonResponse
    {
        try {
            Log::info('[PrendaEditorController] Procesando variaciones', [
                'prenda_id' => $id,
                'user_id' => auth()->id()
            ]);
            
            // Obtener datos de la prenda
            $prenda = $this->prendaEditorService->obtenerPrenda($id);
            
            // Procesar variaciones con el servicio DDD
            $variacionesDTO = $this->variacionProcessor->procesarVariacionesPrenda([
                'variantes' => $prenda->variantes,
                'procesos' => $prenda->procesos,
                'genero' => $prenda->genero
            ]);
            
            Log::info('[PrendaEditorController] Variaciones procesadas exitosamente', [
                'prenda_id' => $id,
                'genero_principal' => $variacionesDTO->genero['nombre'] ?? 'No detectado',
                'tipos_detectados' => $variacionesDTO->tipos_detectados,
                'tiene_variaciones' => $variacionesDTO->tiene_variaciones,
                'es_valida' => $variacionesDTO->es_valida
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $variacionesDTO->toArray()
            ]);
            
        } catch (\Exception $e) {
            Log::error('[PrendaEditorController] Error procesando variaciones', [
                'prenda_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error procesando variaciones: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Procesa procesos de una prenda usando el servicio DDD
     * 
     * POST /api/prendas/{id}/procesar-procesos
     */
    public function procesarProcesos(Request $request, int $id): JsonResponse
    {
        try {
            Log::info('[PrendaEditorController] Procesando procesos', [
                'prenda_id' => $id,
                'user_id' => auth()->id()
            ]);
            
            // Obtener datos de la prenda
            $prenda = $this->prendaEditorService->obtenerPrenda($id);
            
            // Procesar procesos con el servicio DDD
            $procesosDTO = $this->procesoProcessor->procesarProcesosPrenda([
                'procesos' => $prenda->procesos,
                'genero' => $prenda->genero
            ]);
            
            Log::info('[PrendaEditorController] Procesos procesados exitosamente', [
                'prenda_id' => $id,
                'total_procesos' => $procesosDTO->getResumen()['total_procesos'],
                'tipos_detectados' => $procesosDTO->getResumen()['tipos_detectados'],
                'tiene_procesos' => $procesosDTO->tieneProcesos(),
                'es_valida' => $procesosDTO->esValida()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $procesosDTO->toArray()
            ]);
            
        } catch (\Exception $e) {
            Log::error('[PrendaEditorController] Error procesando procesos', [
                'prenda_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error procesando procesos: ' . $e->getMessage()
            ], 500);
        }
    }
}
