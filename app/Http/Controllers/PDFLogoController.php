<?php

namespace App\Http\Controllers;

use App\Application\Services\PdfDesign\LogoPdfDesign;
use App\Models\Cotizacion;
use Illuminate\Http\Request;

/**
 * PDFLogoController - Controlador para generar PDF de Cotizaciones de Logo
 * 
 * Responsabilidades SOLO:
 * - Validar acceso (autenticación/autorización)
 * - Obtener datos de la cotización
 * - Delegar diseño a LogoPdfDesign
 * - Delegar generación PDF a PDFCotizacionHelper
 * - Retornar respuesta HTTP
 * 
 * NO responsable de:
 * - Lógica de diseño HTML (eso es del componente LogoPdfDesign)
 * - Manejo detallado de memoria (helper lo maneja)
 * - Lógica de negocio compleja
 */
class PDFLogoController extends Controller
{
    /**
     * Genera el PDF de logo de una cotización
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
                    $query->select('id', 'cotizacion_id', 'nombre_producto', 'descripcion');
                },
                'prendas.fotos:id,prenda_cot_id,ruta_original,ruta_webp',
                'logoCotizacion' => function($query) {
                    $query->with([
                        'fotos', 
                        'tecnicasPrendas' => function($q) {
                            $q->with(['prenda', 'tipoLogo', 'fotos'])
                              ->select('id', 'logo_cotizacion_id', 'tipo_logo_id', 'prenda_cot_id', 'variaciones_prenda', 'observaciones', 'ubicaciones', 'talla_cantidad');
                        },
                        'telasPrendas' => function($q) {
                            $q->select('id', 'logo_cotizacion_id', 'prenda_cot_id', 'tela', 'color', 'ref', 'img');
                        }
                    ]);
                }
            ])->findOrFail($id);

            // Decodificar el campo especificaciones si viene como string con escape
            if ($cotizacion->especificaciones && is_string($cotizacion->especificaciones)) {
                // Si el JSON viene con escape characters (como el ejemplo), decodificarlo dos veces
                $decoded = json_decode($cotizacion->especificaciones, true);
                if ($decoded && is_string($decoded)) {
                    // Segunda decodificación si es necesario
                    $cotizacion->especificaciones = json_decode($decoded, true);
                } else {
                    $cotizacion->especificaciones = $decoded;
                }
            }

            // 2. Validar permisos de acceso (si es necesario)
            $this->validateAccess($cotizacion);

            // 3. Validar que sea una cotización de logo
            $this->validateLogoCotizacion($cotizacion);

            // 4. Generar diseño HTML usando el componente
            \Log::info('PDFLogoController: Generando diseño HTML', [
                'cotizacion_id' => $id,
                'tiene_prendas' => count($cotizacion->prendas ?? []),
                'tiene_logo' => $cotizacion->logoCotizacion ? true : false
            ]);
            
            $design = new LogoPdfDesign($cotizacion);
            $html = $design->build();

            \Log::info('PDFLogoController: HTML generado correctamente', [
                'cotizacion_id' => $id,
                'html_length' => strlen($html)
            ]);

            // 5. Generar PDF (delegar a helper)
            $pdfContent = $this->generatePdfContent($html);

            // 6. Retornar descarga
            return $this->downloadPdf($pdfContent, $cotizacion);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Error al generar PDF de logo', [
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
        
        if ($user && $user->hasRole('visualizador_cotizaciones_prenda')) {
            abort(403, 'No tienes permiso para ver PDFs de logo. Solo puedes ver PDFs de prenda.');
        }
    }

    /**
     * Valida que sea una cotización de logo (tipo L)
     */
    private function validateLogoCotizacion(Cotizacion $cotizacion): void
    {
        // Permitir si:
        // 1. Es tipo cotizacion_id == 2 (Logo)
        // 2. O es tipo == 'L' 
        // 3. O tiene logo aunque sea combinada
        $esLogo = $cotizacion->tipo_cotizacion_id == 2 || 
                  $cotizacion->tipo === 'L' ||
                  ($cotizacion->logoCotizacion && ($cotizacion->tipo_cotizacion_id == 1 || $cotizacion->tipo === 'PL'));
        
        if (!$esLogo) {
            abort(400, 'Esta cotización no tiene logo. Use el PDF de prenda según corresponda.');
        }
    }

    /**
     * Genera el contenido PDF
     */
    private function generatePdfContent(string $html): string
    {
        // Aumentar límite de memoria y tiempo significativamente
        $memoriaOriginal = ini_get('memory_limit');
        $tiempoOriginal = ini_get('max_execution_time');
        
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '0'); // Sin límite

        try {
            \Log::info('PDFLogoController: Iniciando generación de PDF');
            
            // Limpiar temporales antes de generar
            PDFCotizacionHelper::limpiarTemporales();

            \Log::info('PDFLogoController: Llamando generarPDFConLimpieza');
            
            // Generar PDF
            $pdfContent = PDFCotizacionHelper::generarPDFConLimpieza($html);

            \Log::info('PDFLogoController: PDF generado correctamente', [
                'pdf_size' => strlen($pdfContent)
            ]);

            return $pdfContent;

        } catch (\Exception $e) {
            \Log::error('PDFLogoController: Error en generatePdfContent', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
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
        $filename = 'Cotizacion_' . $cotizacion->id . '_Logo_' . date('Y-m-d') . '.pdf';

        return response()->streamDownload(
            function () use ($pdfContent) {
                echo $pdfContent;
            },
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
