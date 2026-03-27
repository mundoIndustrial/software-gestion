<?php

namespace App\Application\Services\Pdf;

use App\Application\Services\PdfDesign\EppPdfDesign;
use App\Infrastructure\Support\Pdf\PDFCotizacionHelper;
use App\Models\Cotizacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class GenerateEppPdfService
{
    public function generate(int $cotizacionId, ?float $scale): array
    {
        $cotizacion = Cotizacion::with([
            'usuario:id,name',
            'cliente:id,nombre',
            'tipoCotizacion:id,codigo,nombre',
        ])->findOrFail($cotizacionId);

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

        $items = $eppItems->map(function ($item) use ($valores, $imagenes) {
            $vu = $valores[$item->id] ?? null;
            $imgs = $imagenes->get($item->id, collect());

            return [
                'id' => $item->id,
                'nombre' => $item->nombre ?? 'Sin nombre',
                'cantidad' => (int) ($item->cantidad ?? 1),
                'observaciones' => $item->observaciones ?? null,
                'valor_unitario' => $vu ? $vu->valor_unitario : null,
                'imagenes' => $this->mapImagenes($imgs),
            ];
        })->values()->all();

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

        $prendas = $prendaItems->map(function ($item) use ($valoresPrendas, $imagenesPrendas) {
            $vu = $valoresPrendas[$item->id] ?? null;
            $imgs = $imagenesPrendas->get($item->id, collect());

            return [
                'id' => $item->id,
                'nombre' => $item->descripcion ?? 'Sin nombre',
                'cantidad' => (int) ($item->cantidad ?? 1),
                'observaciones' => $item->observaciones ?? null,
                'valor_unitario' => $vu ? $vu->valor_unitario : null,
                'imagenes' => $this->mapImagenes($imgs),
            ];
        })->values()->all();

        $html = (new EppPdfDesign($cotizacion, [
            'epp_cotizacion' => $eppCot,
            'items' => array_merge($items, $prendas),
        ]))->build();

        return [
            'pdfContent' => $this->generatePdfContentAutoScale($html, $scale),
            'filename' => 'Cotizacion_' . $cotizacion->id . '_EPP_' . date('Y-m-d') . '.pdf',
        ];
    }

    private function validateEppCotizacion(Cotizacion $cotizacion): void
    {
        $codigo = ($cotizacion->tipoCotizacion?->codigo ?? $cotizacion->tipo ?? null);
        $codigo = is_string($codigo) ? strtoupper(trim($codigo)) : null;

        if ($codigo !== 'EPP') {
            abort(400, 'Esta cotización no es de tipo EPP.');
        }
    }

    private function mapImagenes($imagenes): array
    {
        return $imagenes->map(function ($row) {
            $ruta = $row->ruta ?? null;
            if (!$ruta) {
                return null;
            }

            try {
                return Storage::disk('public')->path($ruta);
            } catch (\Exception $e) {
                return null;
            }
        })->filter()->values()->all();
    }

    private function generatePdfContentAutoScale(string $html, ?float $scale): string
    {
        if ($scale === null) {
            PDFCotizacionHelper::limpiarTemporales();
            return PDFCotizacionHelper::generarPDFAutoScaleConLimpieza($html);
        }

        return $this->generatePdfContent($html, $this->clampScale($scale));
    }

    private function clampScale(float $scale): float
    {
        if ($scale < 0.75) {
            return 0.75;
        }

        if ($scale > 1.0) {
            return 1.0;
        }

        return $scale;
    }

    private function generatePdfContent(string $html, float $scale): string
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
}

