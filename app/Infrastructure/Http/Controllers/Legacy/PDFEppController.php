<?php

namespace App\Infrastructure\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Infrastructure\Support\Pdf\PDFCotizacionHelper;

use App\Application\Services\PdfDesign\EppPdfDesign;
use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PDFEppController extends Controller
{
    public function generate($id, Request $request)
    {
        try {
            $cotizacion = Cotizacion::with([
                'usuario:id,name',
                'cliente:id,nombre',
                'tipoCotizacion:id,codigo,nombre',
            ])->findOrFail($id);

            $this->validateEppCotizacion($cotizacion);

            $eppCot = DB::table('epp_cotizacion')->where('cotizacion_id', $cotizacion->id)->first();
            $eppItems = DB::table('epp_items_cot')->where('cotizacion_id', $cotizacion->id)->orderBy('id')->get();

            $valores = DB::table('epp_valor_unitario')
                ->whereIn('epp_item_id', $eppItems->pluck('id')->all())
                ->get()
                ->keyBy('epp_item_id');

            $imagenes = DB::table('epp_img_cot')
                ->whereIn('epp_item_id', $eppItems->pluck('id')->all())
                ->orderBy('id')
                ->get()
                ->groupBy('epp_item_id');

            $items = $eppItems->map(function ($it) use ($valores, $imagenes) {
                $vu = $valores[$it->id] ?? null;
                $imgs = $imagenes->get($it->id, collect());

                $imgPaths = $imgs->map(function ($row) {
                    $ruta = $row->ruta ?? null;
                    if (!$ruta) return null;
                    try {
                        $path = Storage::disk('public')->path($ruta);
                        return $path;
                    } catch (\Exception $e) {
                        return null;
                    }
                })->filter()->values()->all();

                return [
                    'id' => $it->id,
                    'nombre' => $it->nombre ?? 'Sin nombre',
                    'cantidad' => (int)($it->cantidad ?? 1),
                    'observaciones' => $it->observaciones ?? null,
                    'valor_unitario' => $vu ? $vu->valor_unitario : null,
                    'imagenes' => $imgPaths,
                ];
            })->values()->all();

            // ============ AGREGAR PRENDAS AL PDF ============
            $prendaItems = DB::table('prenda_items_cot')->where('cotizacion_id', $cotizacion->id)->orderBy('id')->get();

            $valoresPrendas = DB::table('prenda_valor_unitario')
                ->whereIn('prenda_item_id', $prendaItems->pluck('id')->all())
                ->get()
                ->keyBy('prenda_item_id');

            $imagenesPrendas = DB::table('prenda_img_cot')
                ->whereIn('prenda_item_id', $prendaItems->pluck('id')->all())
                ->orderBy('id')
                ->get()
                ->groupBy('prenda_item_id');

            $prendas = $prendaItems->map(function ($it) use ($valoresPrendas, $imagenesPrendas) {
                $vu = $valoresPrendas[$it->id] ?? null;
                $imgs = $imagenesPrendas->get($it->id, collect());

                $imgPaths = $imgs->map(function ($row) {
                    $ruta = $row->ruta ?? null;
                    if (!$ruta) return null;
                    try {
                        $path = Storage::disk('public')->path($ruta);
                        return $path;
                    } catch (\Exception $e) {
                        return null;
                    }
                })->filter()->values()->all();

                return [
                    'id' => $it->id,
                    'nombre' => $it->descripcion ?? 'Sin nombre',
                    'cantidad' => (int)($it->cantidad ?? 1),
                    'observaciones' => $it->observaciones ?? null,
                    'valor_unitario' => $vu ? $vu->valor_unitario : null,
                    'imagenes' => $imgPaths,
                ];
            })->values()->all();

            // Combinar EPPs y prendas en un solo array
            $items = array_merge($items, $prendas);

            $design = new EppPdfDesign($cotizacion, [
                'epp_cotizacion' => $eppCot,
                'items' => $items,
            ]);

            $html = $design->build();

            $pdfContent = $this->generatePdfContentAutoScale($html, $request);

            return $this->downloadPdf($request, $pdfContent, $cotizacion);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error al generar PDF de EPP', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function validateEppCotizacion(Cotizacion $cotizacion): void
    {
        $codigo = ($cotizacion->tipoCotizacion?->codigo ?? $cotizacion->tipo ?? null);
        $codigo = is_string($codigo) ? strtoupper(trim($codigo)) : null;

        if ($codigo !== 'EPP') {
            abort(400, 'Esta cotización no es de tipo EPP.');
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

    private function generatePdfContent(string $html, float $scale = 1.0): string
    {
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
            PDFCotizacionHelper::limpiarTemporales();
            return PDFCotizacionHelper::generarPDFConLimpieza($html, [
                'dpi' => $dpi,
                'img_dpi' => $dpi,
            ]);
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

    private function downloadPdf(Request $request, string $pdfContent, Cotizacion $cotizacion)
    {
        $filename = 'Cotizacion_' . $cotizacion->id . '_EPP_' . date('Y-m-d') . '.pdf';
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
