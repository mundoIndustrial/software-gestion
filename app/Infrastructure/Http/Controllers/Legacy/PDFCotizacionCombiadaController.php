<?php

namespace App\Infrastructure\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Infrastructure\Support\Pdf\PDFCotizacionHelper;

use App\Application\Services\PdfDesign\CombiadaPdfDesign;
use App\Models\Cotizacion;
use App\Models\TallasCostosCot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
                        'tallas:id,prenda_cot_id,talla,color,genero_id,cantidad',
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

            $tallasCostos = TallasCostosCot::query()
                ->where('cotizacion_id', $cotizacion->id)
                ->whereIn('prenda_cot_id', $cotizacion->prendas->pluck('id'))
                ->get()
                ->keyBy('prenda_cot_id');

            foreach ($cotizacion->prendas as $prenda) {
                $prenda->tallas_costos_descripcion = $tallasCostos->get($prenda->id)?->descripcion;
            }

            $obsRows = collect();
            if (Schema::hasTable('logo_observacion_prenda_cot')) {
                $obsRows = DB::table('logo_observacion_prenda_cot')
                    ->where('cotizacion_id', $cotizacion->id)
                    ->get(['prenda_cot_id', 'observacion'])
                    ->keyBy('prenda_cot_id');
            }

            foreach ($cotizacion->prendas as $prenda) {
                $row = $prenda && isset($prenda->id) ? ($obsRows[$prenda->id] ?? null) : null;
                $prenda->logo_observacion = $row ? ($row->observacion ?? null) : null;
            }

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
            // Una cotización se considera combinada para el PDF SOLO si tiene prendas Y logo.
            // Si solo llenaron una parte (ej: solo paso 2), redirigir al PDF correcto.
            $tienePrendas = ($cotizacion->prendas && $cotizacion->prendas->count() > 0);
            $tieneLogo = !is_null($cotizacion->logoCotizacion);
            $esCombinada = $tienePrendas && $tieneLogo;

            if (!$esCombinada) {
                if ($tieneLogo && !$tienePrendas) {
                    return redirect("/asesores/cotizacion/{$cotizacion->id}/pdf/logo");
                }

                // Default: prenda
                return redirect("/asesores/cotizacion/{$cotizacion->id}/pdf/prenda");
            }

            // 4. Generar diseño HTML usando el componente
            \Log::info("Generando diseño HTML...");
            $design = new CombiadaPdfDesign($cotizacion);
            $html = $design->build();
            
            \Log::info("HTML generado, longitud: " . strlen($html) . " caracteres");

            // 5. Generar PDF (delegar a helper)
            \Log::info("Generando contenido PDF...");
            $pdfContent = $this->generatePdfContentAutoScale($html, $request);
            
            \Log::info("PDF generado, tamano: " . strlen($pdfContent) . " bytes");

            // 6. Retornar descarga
            \Log::info("Retornando descarga...");
            return $this->downloadPdf($request, $pdfContent, $cotizacion);

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

        PDFCotizacionHelper::limpiarTemporales();
        return PDFCotizacionHelper::generarPDFAutoScaleConLimpieza($html);
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
                abort(403, 'No tienes permiso para ver PDFs de cotizaciones combinadas. Solo puedes ver PDFs de logo.');
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

        $dpiBase = 96;
        $dpi = (int) round($dpiBase / max(0.01, $scale));
        if ($dpi < 96) {
            $dpi = 96;
        }
        if ($dpi > 160) {
            $dpi = 160;
        }

        try {
            // Limpiar temporales antes de generar
            PDFCotizacionHelper::limpiarTemporales();

            // Generar PDF
            $pdfContent = PDFCotizacionHelper::generarPDFConLimpieza($html, [
                'dpi' => $dpi,
                'img_dpi' => $dpi,
            ]);

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
    private function downloadPdf(Request $request, string $pdfContent, Cotizacion $cotizacion)
    {
        $filename = 'pdf_cotizacion_combinada_' . $cotizacion->numero_cotizacion . '_' . date('Y-m-d') . '.pdf';
        $dispositionType = $request->boolean('download') ? 'attachment' : 'inline';

        \Log::info("Iniciando descarga del PDF con nombre: {$filename}, tamano: " . strlen($pdfContent) . " bytes");

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $dispositionType . '; filename="' . $filename . '"')
            ->header('Content-Length', strlen($pdfContent))
            ->header('Cache-Control', 'private, max-age=0, must-revalidate')
            ->header('Pragma', 'public')
            ->header('Expires', '0');
    }
}
