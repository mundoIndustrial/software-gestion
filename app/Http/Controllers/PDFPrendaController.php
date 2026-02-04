<?php

namespace App\Http\Controllers;

use App\Application\Services\PdfDesign\PrendaPdfDesign;
use App\Models\Cotizacion;
use Illuminate\Http\Request;

/**
 * PDFPrendaController - Controlador para generar PDF de Prendas
 * 
 * Responsabilidades SOLO:
 * - Validar acceso (autenticación/autorización)
 * - Obtener datos de la cotización
 * - Delegar diseño a PrendaPdfDesign
 * - Delegar generación PDF a PDFCotizacionHelper
 * - Retornar respuesta HTTP
 * 
 * NO responsable de:
 * - Lógica de diseño HTML (eso es del componente PrendaPdfDesign)
 * - Manejo detallado de memoria (helper lo maneja)
 * - Lógica de negocio compleja
 */
class PDFPrendaController extends Controller
{
    /**
     * Genera el PDF de prenda de una cotización
     */
    public function generate($id, Request $request)
    {
        try {
            // 1. Validar que exista la cotización
            $cotizacion = Cotizacion::with([
                'usuario:id,name',
                'cliente:id,nombre',
                'tipoCotizacion:id,codigo,nombre',
                'prendas' => function($query) {
                    $query->select('id', 'cotizacion_id', 'nombre_producto', 'descripcion', 'texto_personalizado_tallas');
                },
                'prendas.fotos:id,prenda_cot_id,ruta_original,ruta_webp',
                'prendas.telaFotos:id,prenda_cot_id,ruta_original,ruta_webp',
                'prendas.tallas:id,prenda_cot_id,talla,cantidad',
                'prendas.variantes:id,prenda_cot_id,color,tipo_manga_id,tipo_broche_id,tiene_reflectivo,obs_reflectivo,tiene_bolsillos,obs_bolsillos,aplica_manga,obs_manga,obs_broche,es_jean_pantalon,tipo_jean_pantalon,telas_multiples',
                'prendas.variantes.manga:id,nombre',
                'prendas.variantes.broche:id,nombre',
                'prendas.telas.tela:id,nombre,referencia',
                'prendas.reflectivo' => function($query) {
                    $query->select('id', 'cotizacion_id', 'prenda_cot_id', 'descripcion', 'ubicacion');
                },
                'prendas.reflectivo.fotos:id,reflectivo_cotizacion_id,ruta_original,ruta_webp'
            ])->findOrFail($id);

            // 2. Validar permisos de acceso (si es necesario)
            $this->validateAccess($cotizacion);

            // 3. Generar diseño HTML usando el componente
            $design = new PrendaPdfDesign($cotizacion);
            $html = $design->build();

            // 4. Generar PDF (delegar a helper)
            $pdfContent = $this->generatePdfContent($html);

            // 5. Retornar descarga
            return $this->downloadPdf($pdfContent, $cotizacion);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Error al generar PDF de prenda', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
            abort(403, 'No tienes permiso para ver PDFs de prenda. Solo puedes ver PDFs de logo.');
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
        $filename = 'Cotizacion_' . $cotizacion->id . '_Prenda_' . date('Y-m-d') . '.pdf';

        return response()->streamDownload(
            function () use ($pdfContent) {
                echo $pdfContent;
            },
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
