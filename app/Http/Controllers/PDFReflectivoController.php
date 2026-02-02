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
            // 1. Obtener la cotización con todas sus relaciones
            $cotizacion = Cotizacion::with([
                'usuario:id,name',
                'cliente:id,nombre',
                'tipoCotizacion:id,codigo,nombre',
                'prendaCotReflectivos' => function($query) {
                    $query->with([
                        'prendaCot:id,cotizacion_id,nombre_producto,descripcion',
                        'prendaCot.tallas:id,prenda_cot_id,talla,cantidad',
                        'prendaCot.prendaCotReflectivo:id,prenda_cot_id,color_tela_ref,descripcion,ubicaciones,variaciones',
                        'prendaCot.telaFotos:id,prenda_cot_id,tela_index,ruta_original,ruta_webp'
                    ]);
                },
                'reflectivoPrendas.fotos:id,reflectivo_cotizacion_id,ruta_original,ruta_webp'
            ])->findOrFail($id);

            // 2. Validar que sea una cotización reflectivo (tipo_cotizacion_id = 4 o código RF)
            $esReflectivo = $cotizacion->tipo_cotizacion_id == 4 || 
                           ($cotizacion->tipoCotizacion && $cotizacion->tipoCotizacion->codigo === 'RF');
            
            if (!$esReflectivo) {
                return response()->json(['success' => false, 'message' => 'Esta cotización no es de tipo reflectivo'], 400);
            }

            // 3. Generar diseño HTML usando el componente
            $design = new ReflectivoPdfDesign($cotizacion);
            $html = $design->build();

            // 4. Generar PDF
            $pdfContent = $this->generatePdfContent($html);

            // 5. Retornar descarga
            return $this->downloadPdf($pdfContent, $cotizacion);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Cotización no encontrada'], 404);
        } catch (\Exception $e) {
            \Log::error('Error al generar PDF de reflectivo: ' . $e->getMessage(), [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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


