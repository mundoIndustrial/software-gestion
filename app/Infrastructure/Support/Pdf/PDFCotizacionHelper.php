<?php

namespace App\Infrastructure\Support\Pdf;

use Mpdf\Mpdf;

/**
 * Helper para generar PDFs con gestión de memoria optimizada.
 */
class PDFCotizacionHelper
{
    /**
     * Genera un PDF y libera memoria inmediatamente.
     */
    public static function generarPDFConLimpieza(string $html, array $config = []): string
    {
        try {
            \Log::info('Iniciando generación PDF con HTML de longitud: ' . strlen($html));

            $defaultConfig = [
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 5,
                'margin_right' => 5,
                'margin_top' => 0,
                'margin_bottom' => 5,
                'margin_header' => 0,
                'margin_footer' => 0,
                'tempDir' => storage_path('app/temp'),
                'allow_output_buffering' => true,
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'use_kwt' => true,
                'setAutoTopMargin' => false,
                'setAutoBottomMargin' => 'stretch',
                'debug' => false,
                'showImageErrors' => false,
                'ignore_invalid_utf8' => true,
            ];

            $config = array_merge($defaultConfig, $config);
            \Log::info('Creando instancia mPDF con configuración...');

            ini_set('pcre.backtrack_limit', '50000000');
            ini_set('pcre.recursion_limit', '50000000');

            $mpdf = new Mpdf($config);
            $mpdf->shrink_tables_to_fit = 1;
            $mpdf->keep_table_proportions = true;

            try {
                \Log::info('Instancia mPDF creada, escribiendo HTML...');
                $mpdf->WriteHTML($html);
                \Log::info('HTML escrito en mPDF, generando contenido...');
                $pdfContent = $mpdf->Output('', 'S');
                \Log::info('PDF generado, tamano: ' . strlen($pdfContent) . ' bytes');

                unset($mpdf);
                unset($html);
                gc_collect_cycles();
                gc_mem_caches();

                \Log::info('Memoria limpiada, retornando contenido PDF');
                return $pdfContent;
            } catch (\Mpdf\MpdfException $e) {
                \Log::error('Error específico de mPDF: ' . $e->getMessage(), [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                throw new \Exception('Error al generar PDF: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            \Log::error('Error en generarPDFConLimpieza: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('Error al generar PDF: ' . $e->getMessage());
        }
    }

    public static function generarPDFAutoScaleConLimpieza(
        string $html,
        array $config = [],
        array $scales = [1.0, 0.95, 0.9, 0.85, 0.8, 0.75]
    ): string {
        $scales = array_values(array_filter($scales, fn ($s) => is_numeric($s)));
        if (empty($scales)) {
            $scales = [1.0];
        }

        $lastPdf = null;

        foreach ($scales as $scale) {
            $scale = (float) $scale;
            if ($scale <= 0) {
                continue;
            }

            $dpiBase = 96;
            $dpi = (int) round($dpiBase / max(0.01, $scale));
            if ($dpi < 96) {
                $dpi = 96;
            }
            if ($dpi > 160) {
                $dpi = 160;
            }

            $attemptConfig = array_merge($config, [
                'dpi' => $dpi,
                'img_dpi' => $dpi,
            ]);

            try {
                $defaultConfig = [
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 5,
                    'margin_right' => 5,
                    'margin_top' => 0,
                    'margin_bottom' => 5,
                    'margin_header' => 0,
                    'margin_footer' => 0,
                    'tempDir' => storage_path('app/temp'),
                    'allow_output_buffering' => true,
                    'autoScriptToLang' => true,
                    'autoLangToFont' => true,
                    'use_kwt' => true,
                    'setAutoTopMargin' => false,
                    'setAutoBottomMargin' => 'stretch',
                    'debug' => false,
                    'showImageErrors' => false,
                    'ignore_invalid_utf8' => true,
                ];

                $mpdfConfig = array_merge($defaultConfig, $attemptConfig);
                $mpdf = new Mpdf($mpdfConfig);
                $mpdf->shrink_tables_to_fit = 1;
                $mpdf->keep_table_proportions = true;

                $mpdf->WriteHTML($html);
                $pdfContent = $mpdf->Output('', 'S');
                $pageCount = (int) ($mpdf->page ?? 0);

                unset($mpdf);
                gc_collect_cycles();
                gc_mem_caches();

                $lastPdf = $pdfContent;

                if ($pageCount <= 1) {
                    return $pdfContent;
                }
            } catch (\Throwable $e) {
                \Log::warning('Auto-scale PDF: intento fallido', [
                    'scale' => $scale,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (is_string($lastPdf)) {
            return $lastPdf;
        }

        return self::generarPDFConLimpieza($html, $config);
    }

    /**
     * Limpia archivos temporales antiguos.
     */
    public static function limpiarTemporales(): void
    {
        $tempDir = storage_path('app/temp');

        if (!is_dir($tempDir)) {
            return;
        }

        $files = glob($tempDir . '/*');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) >= 3600)) {
                @unlink($file);
            }
        }
    }
}
