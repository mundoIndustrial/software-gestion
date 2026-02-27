<?php

namespace App\Http\Controllers;

use App\Application\Services\PdfDesign\PrendaPdfDesign;
use App\Models\Cotizacion;
use App\Models\TallasCostosCot;
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
                'prendas.tallas:id,prenda_cot_id,talla,color,genero_id,cantidad',
                'prendas.variantes:id,prenda_cot_id,color,tipo_manga_id,tipo_broche_id,tiene_reflectivo,obs_reflectivo,tiene_bolsillos,obs_bolsillos,aplica_manga,obs_manga,obs_broche,es_jean_pantalon,tipo_jean_pantalon,telas_multiples',
                'prendas.variantes.manga:id,nombre',
                'prendas.variantes.broche:id,nombre',
                'prendas.telas.tela:id,nombre,referencia'
            ])->findOrFail($id);

            $tallasCostos = TallasCostosCot::query()
                ->where('cotizacion_id', $cotizacion->id)
                ->whereIn('prenda_cot_id', $cotizacion->prendas->pluck('id'))
                ->get()
                ->keyBy('prenda_cot_id');

            foreach ($cotizacion->prendas as $prenda) {
                $prenda->tallas_costos_descripcion = $tallasCostos->get($prenda->id)?->descripcion;
            }

            // 2. Validar permisos de acceso (si es necesario)
            $this->validateAccess($cotizacion);

            // 3. Generar diseño HTML usando el componente
            $design = new PrendaPdfDesign($cotizacion);
            $html = $design->build();

            // 4. Generar PDF (delegar a helper)
            $pdfContent = $this->generatePdfContentAutoScale($html, $request);

            // 5. Retornar descarga
            return $this->downloadPdf($request, $pdfContent, $cotizacion);

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

        if (!$user) {
            abort(401, 'Usuario no autenticado');
        }

        // Si el usuario es visualizador de logo, bloquear SOLO si no tiene un rol con acceso completo.
        // (Hay usuarios que pueden tener múltiples roles: p.ej. asesor + visualizador)
        if ($user->hasRole('visualizador_cotizaciones_logo')) {
            $hasPrivilegedRole = $user->hasRole('admin') ||
                $user->hasRole('contador') ||
                $user->hasRole('aprobador_cotizaciones') ||
                $user->hasRole('asesor');

            if (!$hasPrivilegedRole) {
                abort(403, 'No tienes permiso para ver PDFs de prenda. Solo puedes ver PDFs de logo.');
            }
        }
    }

    private function parseScale(Request $request): float
    {
        $scale = $request->query('scale', 1);
        $scale = is_numeric($scale) ? (float) $scale : 1.0;

        if ($scale < 0.75) {
            $scale = 0.75;
        }
        if ($scale > 1.0) {
            $scale = 1.0;
        }

        return $scale;
    }

    private function generatePdfContentAutoScale(string $html, Request $request): string
    {
        if ($request->query->has('scale')) {
            $scale = $this->parseScale($request);
            return $this->generatePdfContent($html, $scale);
        }

        $memoriaOriginal = ini_get('memory_limit');
        $tiempoOriginal = ini_get('max_execution_time');

        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', '120');

        try {
            PDFCotizacionHelper::limpiarTemporales();
            return PDFCotizacionHelper::generarPDFAutoScaleConLimpieza($html);
        } finally {
            ini_set('memory_limit', $memoriaOriginal);
            ini_set('max_execution_time', $tiempoOriginal);

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
     * Genera el contenido PDF
     */
    private function generatePdfContent(string $html, float $scale = 1.0): string
    {
        // Aumentar límite de memoria y tiempo
        $memoriaOriginal = ini_get('memory_limit');
        $tiempoOriginal = ini_get('max_execution_time');
        
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', '120');

        // mPDF escala “encogiendo” el contenido si subimos el DPI.
        // scale=1.00 => dpi base
        // scale=0.90 => dpi ~ base/0.90 (más dpi => más contenido por página)
        $dpiBase = 96;
        $dpi = (int) round($dpiBase / max(0.01, $scale));
        if ($dpi < 96) {
            $dpi = 96;
        }
        if ($dpi > 160) {
            $dpi = 160;
        }

        try {
            PDFCotizacionHelper::limpiarTemporales();

            return PDFCotizacionHelper::generarPDFConLimpieza($html, [
                'dpi' => $dpi,
                'img_dpi' => $dpi,
            ]);
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
    private function downloadPdf(Request $request, string $pdfContent, Cotizacion $cotizacion)
    {
        $filename = 'Cotizacion_' . $cotizacion->id . '_Prenda_' . date('Y-m-d') . '.pdf';
        $dispositionType = $request->boolean('download') ? 'attachment' : 'inline';

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $dispositionType . '; filename="' . $filename . '"')
            ->header('Content-Length', strlen($pdfContent))
            ->header('Cache-Control', 'private, max-age=0, must-revalidate')
            ->header('Pragma', 'public')
            ->header('Expires', '0');
    }
}
