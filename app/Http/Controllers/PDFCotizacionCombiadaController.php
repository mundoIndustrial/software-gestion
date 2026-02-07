<?php

namespace App\Http\Controllers;

use App\Application\Services\PdfDesign\CombiadaPdfDesign;
use App\Models\Cotizacion;
use Illuminate\Http\Request;

/**
 * PDFCotizacionCombiadaController - Controlador para generar PDF de Cotizaciones Combinadas
 * 
 * Responsabilidades SOLO:
 * - Validar acceso (autenticación/autorización)
 * - Obtener datos de la cotización
 * - Delegar diseño a CombiadaPdfDesign
 * - Delegar generación PDF a PDFCotizacionHelper
 * - Retornar respuesta HTTP
 * 
 * NO responsable de:
 * - Lógica de diseño HTML (eso es del componente CombiadaPdfDesign)
 * - Manejo detallado de memoria (helper lo maneja)
 * - Lógica de negocio compleja
 */
class PDFCotizacionCombiadaController extends Controller
{
    /**
     * Genera el PDF de cotización combinada
     */
    public function generate($id, Request $request)
    {
        try {
            // Log de inicio
            \Log::info("Iniciando generación PDF combinada para cotización ID: {$id}");
            
            // 1. Validar que exista la cotización
            $cotizacion = Cotizacion::with([
                'usuario:id,name',
                'cliente:id,nombre',
                'tipoCotizacion:id,codigo,nombre',
                'prendas' => function($query) {
                    $query->with([
                        'fotos:id,prenda_cot_id,ruta_original,ruta_webp',
                        'telaFotos:id,prenda_cot_id,ruta_original,ruta_webp',
                        'tallas:id,prenda_cot_id,talla,genero_id,cantidad',
                        'variantes:id,prenda_cot_id,color,tipo_manga_id,tipo_broche_id,tiene_reflectivo,obs_reflectivo,tiene_bolsillos,obs_bolsillos,aplica_manga,obs_manga,obs_broche,es_jean_pantalon,tipo_jean_pantalon,telas_multiples',
                        'variantes.manga:id,nombre',
                        'variantes.broche:id,nombre',
                        'telas.tela:id,nombre,referencia'
                    ]);
                },
                'logoCotizacion' => function($query) {
                    $query->with(['fotos', 'tecnicasPrendas' => function($q) {
                        $q->with(['prenda', 'tipoLogo', 'fotos']);
                    }]);
                }
            ])->findOrFail($id);

            \Log::info("Cotización encontrada: " . json_encode([
                'id' => $cotizacion->id,
                'numero' => $cotizacion->numero_cotizacion,
                'tipo' => $cotizacion->tipo,
                'prendas_count' => $cotizacion->prendas?->count() ?? 0,
                'has_logo' => !is_null($cotizacion->logoCotizacion)
            ]));

            // 2. Validar permisos de acceso (si es necesario)
            $this->validateAccess($cotizacion);

            // 3. Validar que sea una cotización combinada
            $this->validateCombiadaCotizacion($cotizacion);

            // 4. Generar diseño HTML usando el componente
            \Log::info("Generando diseño HTML...");
            $design = new CombiadaPdfDesign($cotizacion);
            $html = $design->build();
            
            \Log::info("HTML generado, longitud: " . strlen($html) . " caracteres");

            // 5. Generar PDF (delegar a helper)
            \Log::info("Generando contenido PDF...");
            $pdfContent = $this->generatePdfContent($html);
            
            \Log::info("PDF generado, tamaño: " . strlen($pdfContent) . " bytes");

            // 6. Retornar descarga
            \Log::info("Retornando descarga...");
            return $this->downloadPdf($pdfContent, $cotizacion);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Cotización no encontrada', ['cotizacion_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Error al generar PDF de cotización combinada', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // En desarrollo, mostrar error completo
            if (app()->environment('local')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar PDF: ' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valida si el usuario tiene acceso a este PDF
     */
    private function validateAccess(Cotizacion $cotizacion): void
    {
        $user = auth()->user();
        
        if ($user && $user->hasRole('visualizador_cotizaciones_logo')) {
            abort(403, 'No tienes permiso para ver PDFs de cotizaciones combinadas. Solo puedes ver PDFs de logo.');
        }
    }

    /**
     * Valida que sea una cotización combinada (tipo PL)
     */
    private function validateCombiadaCotizacion(Cotizacion $cotizacion): void
    {
        $esCombinada = $cotizacion->tipo === 'PL' || 
                       ($cotizacion->prendas && $cotizacion->prendas->count() > 0 && $cotizacion->logoCotizacion);
        
        if (!$esCombinada) {
            abort(400, 'Esta cotización no es de tipo combinada. Use el PDF de prenda o logo según corresponda.');
        }
    }

    /**
     * Genera el contenido PDF
     */
    private function generatePdfContent(string $html): string
    {
        // Aumentar límite de memoria y tiempo
        $memoriaOriginal = ini_get('memory_limit');
        $tiempoOriginal = ini_get('max_execution_time');
        
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', '120');

        try {
            // Limpiar temporales antes de generar
            PDFCotizacionHelper::limpiarTemporales();

            // Generar PDF
            $pdfContent = PDFCotizacionHelper::generarPDFConLimpieza($html);

            return $pdfContent;

        } finally {
            // Restaurar límites
            ini_set('memory_limit', $memoriaOriginal);
            ini_set('max_execution_time', $tiempoOriginal);

            // Limpiar memoria
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
                gc_collect_cycles();
            }

            if (function_exists('gc_mem_caches')) {
                gc_mem_caches();
            }
        }
    }

    /**
     * Descarga el PDF
     */
    private function downloadPdf(string $pdfContent, Cotizacion $cotizacion)
    {
        $filename = 'pdf_cotizacion_combinada_' . $cotizacion->numero_cotizacion . '_' . date('Y-m-d') . '.pdf';

        \Log::info("Iniciando descarga del PDF con nombre: {$filename}, tamaño: " . strlen($pdfContent) . " bytes");

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Length', strlen($pdfContent))
            ->header('Cache-Control', 'private, max-age=0, must-revalidate')
            ->header('Pragma', 'public')
            ->header('Expires', '0');
    }
}
