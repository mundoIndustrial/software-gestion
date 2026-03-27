<?php

namespace App\Application\Services\Pdf;

use App\Application\Services\PdfDesign\LogoPdfDesign;
use App\Infrastructure\Support\Pdf\PDFCotizacionHelper;
use App\Models\Cotizacion;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class GenerateLogoPdfService
{
    public function generate(int $cotizacionId, ?float $scale, ?Authenticatable $user): array
    {
        $cotizacion = Cotizacion::with([
            'usuario:id,name',
            'cliente:id,nombre',
            'tipoCotizacion:id,codigo,nombre',
            'prendas' => function ($query) {
                $query->select('id', 'cotizacion_id', 'nombre_producto', 'descripcion');
            },
            'prendas.fotos:id,prenda_cot_id,ruta_original,ruta_webp',
            'logoCotizacion' => function ($query) {
                $query->with([
                    'fotos',
                    'tecnicasPrendas' => function ($q) {
                        $q->with(['prenda', 'tipoLogo', 'fotos'])
                            ->select('id', 'logo_cotizacion_id', 'tipo_logo_id', 'prenda_cot_id', 'variaciones_prenda', 'observaciones', 'ubicaciones', 'talla_cantidad');
                    },
                    'telasPrendas' => function ($q) {
                        $q->select('id', 'logo_cotizacion_id', 'prenda_cot_id', 'tela', 'color', 'ref', 'img');
                    },
                ]);
            },
        ])->findOrFail($cotizacionId);

        $obsRows = collect();
        if (Schema::hasTable('logo_observacion_prenda_cot')) {
            $obsRows = DB::table('logo_observacion_prenda_cot')
                ->where('cotizacion_id', $cotizacion->id)
                ->get(['prenda_cot_id', 'observacion'])
                ->keyBy('prenda_cot_id');
        }

        if ($cotizacion->relationLoaded('prendas') && $cotizacion->prendas) {
            foreach ($cotizacion->prendas as $prenda) {
                $row = $prenda && isset($prenda->id) ? ($obsRows[$prenda->id] ?? null) : null;
                $prenda->logo_observacion = $row ? ($row->observacion ?? null) : null;
            }
        }

        if ($cotizacion->especificaciones && is_string($cotizacion->especificaciones)) {
            $decoded = json_decode($cotizacion->especificaciones, true);
            $cotizacion->especificaciones = ($decoded && is_string($decoded))
                ? json_decode($decoded, true)
                : $decoded;
        }

        $this->validateAccess($user);
        $this->validateLogoCotizacion($cotizacion);

        $html = (new LogoPdfDesign($cotizacion))->build();

        return [
            'pdfContent' => $this->generatePdfContentAutoScale($html, $scale),
            'filename' => 'Cotizacion_' . $cotizacion->id . '_Logo_' . date('Y-m-d') . '.pdf',
        ];
    }

    private function validateAccess(?Authenticatable $user): void
    {
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('visualizador_cotizaciones_prenda')) {
            abort(403, 'No tienes permiso para ver PDFs de logo. Solo puedes ver PDFs de prenda.');
        }
    }

    private function validateLogoCotizacion(Cotizacion $cotizacion): void
    {
        $esLogo = $cotizacion->tipo_cotizacion_id == 2
            || $cotizacion->tipo === 'L'
            || ($cotizacion->logoCotizacion && ($cotizacion->tipo_cotizacion_id == 1 || $cotizacion->tipo === 'PL'));

        if (!$esLogo) {
            abort(400, 'Esta cotización no tiene logo. Use el PDF de prenda según corresponda.');
        }
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

        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '0');

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

