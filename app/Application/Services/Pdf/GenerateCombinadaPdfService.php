<?php

namespace App\Application\Services\Pdf;

use App\Application\Services\PdfDesign\CombiadaPdfDesign;
use App\Infrastructure\Support\Pdf\PDFCotizacionHelper;
use App\Models\Cotizacion;
use App\Models\TallasCostosCot;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class GenerateCombinadaPdfService
{
    public function generate(int $cotizacionId, ?float $scale, ?Authenticatable $user): array
    {
        $cotizacion = Cotizacion::with([
            'usuario:id,name',
            'cliente:id,nombre',
            'tipoCotizacion:id,codigo,nombre',
            'prendas' => function ($query) {
                $query->with([
                    'fotos:id,prenda_cot_id,ruta_original,ruta_webp',
                    'telaFotos:id,prenda_cot_id,ruta_original,ruta_webp',
                    'tallas:id,prenda_cot_id,talla,color,genero_id,cantidad',
                    'variantes:id,prenda_cot_id,color,tipo_manga_id,tipo_broche_id,tiene_reflectivo,obs_reflectivo,tiene_bolsillos,obs_bolsillos,aplica_manga,obs_manga,obs_broche,es_jean_pantalon,tipo_jean_pantalon,telas_multiples',
                    'variantes.manga:id,nombre',
                    'variantes.broche:id,nombre',
                    'telas.tela:id,nombre,referencia',
                ]);
            },
            'logoCotizacion' => function ($query) {
                $query->with(['fotos', 'tecnicasPrendas' => function ($q) {
                    $q->with(['prenda', 'tipoLogo', 'fotos']);
                }]);
            },
        ])->findOrFail($cotizacionId);

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

        $this->validateAccess($user);

        $tienePrendas = ($cotizacion->prendas && $cotizacion->prendas->count() > 0);
        $tieneLogo = !is_null($cotizacion->logoCotizacion);
        $esCombinada = $tienePrendas && $tieneLogo;

        if (!$esCombinada) {
            if ($tieneLogo && !$tienePrendas) {
                return ['redirectUrl' => "/asesores/cotizacion/{$cotizacion->id}/pdf/logo"];
            }

            return ['redirectUrl' => "/asesores/cotizacion/{$cotizacion->id}/pdf/prenda"];
        }

        $html = (new CombiadaPdfDesign($cotizacion))->build();

        return [
            'pdfContent' => $this->generatePdfContentAutoScale($html, $scale),
            'filename' => 'pdf_cotizacion_combinada_' . $cotizacion->numero_cotizacion . '_' . date('Y-m-d') . '.pdf',
        ];
    }

    private function validateAccess(?Authenticatable $user): void
    {
        if (!$user) {
            abort(401, 'Usuario no autenticado');
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('visualizador_cotizaciones_logo')) {
            $hasPrivilegedRole = $user->hasRole('admin')
                || $user->hasRole('contador')
                || $user->hasRole('aprobador_cotizaciones')
                || $user->hasRole('asesor');

            if (!$hasPrivilegedRole) {
                abort(403, 'No tienes permiso para ver PDFs de cotizaciones combinadas. Solo puedes ver PDFs de logo.');
            }
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

