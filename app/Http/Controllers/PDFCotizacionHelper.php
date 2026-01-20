<?php

namespace App\Http\Controllers;

use Mpdf\Mpdf;

/**
 * Helper para generar PDFs con gestión de memoria optimizada
 */
class PDFCotizacionHelper
{
    /**
     * Genera un PDF y libera memoria inmediatamente
     */
    public static function generarPDFConLimpieza(string $html, array $config = []): string
    {
        // Configuración por defecto de mPDF
        $defaultConfig = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
            'tempDir' => storage_path('app/temp'), // Usar directorio temporal
        ];
        
        $config = array_merge($defaultConfig, $config);
        
        // Crear PDF
        $mpdf = new Mpdf($config);
        
        // Escribir HTML
        $mpdf->WriteHTML($html);
        
        // Generar contenido
        $pdfContent = $mpdf->Output('', 'S');
        
        //  CRÍTICO: Destruir objeto inmediatamente
        unset($mpdf);
        
        //  Limpiar HTML de memoria
        unset($html);
        
        //  Forzar limpieza
        gc_collect_cycles();
        gc_mem_caches();
        
        return $pdfContent;
    }
    
    /**
     * Limpia archivos temporales antiguos
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
            if (is_file($file)) {
                // Eliminar archivos más antiguos de 1 hora
                if ($now - filemtime($file) >= 3600) {
                    @unlink($file);
                }
            }
        }
    }
}
