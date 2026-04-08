<?php

namespace App\Application\Services\Pdf;

use App\Application\Services\PdfDesign\PrendaPdfDesign;
use App\Infrastructure\Support\Pdf\PDFCotizacionHelper;
use App\Models\Cotizacion;
use App\Models\TallasCostosCot;
use Illuminate\Contracts\Auth\Authenticatable;

final class GeneratePrendaPdfService
{
    public function generate(int $cotizacionId, ?float $scale, ?Authenticatable $user): array
    {
        $cotizacion = Cotizacion::with([
            'usuario:id,name',
            'cliente:id,nombre',
            'tipoCotizacion:id,codigo,nombre',
            'prendas' => function ($query) {
                $query->select('id', 'cotizacion_id', 'nombre_producto', 'descripcion', 'texto_personalizado_tallas');
            },
            'prendas.fotos:id,prenda_cot_id,ruta_original,ruta_webp',
            'prendas.telaFotos:id,prenda_cot_id,ruta_original,ruta_webp',
            'prendas.tallas:id,prenda_cot_id,talla,color,genero_id,cantidad',
            'prendas.variantes:id,prenda_cot_id,color,tipo_manga_id,tipo_broche_id,tiene_reflectivo,obs_reflectivo,tiene_bolsillos,obs_bolsillos,aplica_manga,obs_manga,obs_broche,es_jean_pantalon,tipo_jean_pantalon,telas_multiples',
            'prendas.variantes.manga:id,nombre',
            'prendas.variantes.broche:id,nombre',
            'prendas.telas.tela:id,nombre,referencia',
        ])->findOrFail($cotizacionId);

        $tallasCostos = TallasCostosCot::query()
            ->where('cotizacion_id', $cotizacion->id)
            ->whereIn('prenda_cot_id', $cotizacion->prendas->pluck('id'))
            ->get()
            ->keyBy('prenda_cot_id');

        foreach ($cotizacion->prendas as $prenda) {
            $prenda->tallas_costos_descripcion = $tallasCostos->get($prenda->id)?->descripcion;
        }

        $this->validateAccess($user);

        $html = (new PrendaPdfDesign($cotizacion))->build();
        $pdfContent = $this->generatePdfContentAutoScale($html, $scale);

        return [
            'pdfContent' => $pdfContent,
            'filename' => 'Cotizacion_' . $cotizacion->id . '_Prenda_' . date('Y-m-d') . '.pdf',
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
                abort(403, 'No tienes permiso para ver PDFs de prenda. Solo puedes ver PDFs de logo.');
            }
        }
    }

    private function generatePdfContentAutoScale(string $html, ?float $scale): string
    {
        if ($scale === null) {
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

