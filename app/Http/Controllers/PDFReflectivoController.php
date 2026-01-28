<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Application\Services\PdfDesign\ReflectivoPdfDesign;
use Illuminate\Http\Request;

/**
 * PDFReflectivoController - Controlador para generar PDF de Reflectivo
 * 
 * VERSIÓN SIMPLE: Solo genera un PDF vacío con header
 */
class PDFReflectivoController extends Controller
{
    /**
     * Genera el PDF de reflectivo de una cotización
     */
    public function generate($id, Request $request)
    {
        try {
            // 1. Obtener la cotización
            $cotizacion = Cotizacion::find($id);

            if (!$cotizacion) {
                return response()->json(['success' => false, 'message' => 'Cotización no encontrada'], 404);
            }

            // 2. Cargar prendas de reflectivo con sus datos completos
            $cotizacion->load([
                'prendaCotReflectivos:id,cotizacion_id,prenda_cot_id,variaciones',
                'prendaCotReflectivos.prendaCot:id,cotizacion_id,nombre_producto,descripcion',
                'prendaCotReflectivos.prendaCot.fotos:id,prenda_cot_id,ruta_webp',
                'prendaCotReflectivos.prendaCot.tallas:id,prenda_cot_id,talla,cantidad',
                'reflectivoPrendas.fotos:id,reflectivo_cotizacion_id,ruta_webp'
            ]);

            // 3. Generar diseño HTML usando el componente
            $design = new ReflectivoPdfDesign($cotizacion);
            $html = $design->build();

            // 4. Generar PDF
            $pdfContent = $this->generatePdfContent($html);

            // 5. Retornar descarga
            return $this->downloadPdf($pdfContent, $cotizacion);

        } catch (\Exception $e) {
            \Log::error('Error al generar PDF de reflectivo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al generar PDF: ' . $e->getMessage()], 500);
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
        ini_set('max_execution_time', '60');

        try {
            PDFCotizacionHelper::limpiarTemporales();
            $pdfContent = PDFCotizacionHelper::generarPDFConLimpieza($html);
            return $pdfContent;

        } finally {
            ini_set('memory_limit', $memoriaOriginal);
            ini_set('max_execution_time', $tiempoOriginal);
            
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
    }

    /**
     * Descarga el PDF
     */
    private function downloadPdf(string $pdfContent, Cotizacion $cotizacion)
    {
        $codigo = $cotizacion->numero_cotizacion ?? $cotizacion->id;
        $filename = 'Cotizacion_' . $codigo . '_Reflectivo_' . date('Y-m-d') . '.pdf';

        return response()->streamDownload(
            function () use ($pdfContent) {
                echo $pdfContent;
            },
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}


