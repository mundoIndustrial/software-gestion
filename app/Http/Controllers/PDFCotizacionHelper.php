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
        try {
            \Log::info("Iniciando generación PDF con HTML de longitud: " . strlen($html));
            
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
                'allow_output_buffering' => true,
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'use_kwt' => true, // Enable HTML to PDF conversion
                'setAutoTopMargin' => false,
                'setAutoBottomMargin' => 'stretch',
                'debug' => false, // Desactivar debug en producción
                'showImageErrors' => false, // No mostrar errores de imagen
                'ignore_invalid_utf8' => true, // Ignorar caracteres UTF-8 inválidos
                // Eliminada configuración de fuentes personalizadas - usar fuentes por defecto de mPDF
            ];
            
            $config = array_merge($defaultConfig, $config);
            
            \Log::info("Creando instancia mPDF con configuración...");
            
            // Crear PDF
            $mpdf = new Mpdf($config);
            
            try {
                \Log::info("Instancia mPDF creada, escribiendo HTML...");
                
                // Escribir HTML
                $mpdf->WriteHTML($html);
                
                \Log::info("HTML escrito en mPDF, generando contenido...");
                
                // Generar contenido
                $pdfContent = $mpdf->Output('', 'S');
                
                \Log::info("PDF generado, tamaño: " . strlen($pdfContent) . " bytes");
                
                //  CRÍTICO: Destruir objeto inmediatamente
                unset($mpdf);
                
                //  Limpiar HTML de memoria
                unset($html);
                
                //  Forzar limpieza
                gc_collect_cycles();
                gc_mem_caches();
                
                \Log::info("Memoria limpiada, retornando contenido PDF");
                
                return $pdfContent;
                
            } catch (\Mpdf\MpdfException $e) {
                \Log::error("Error específico de mPDF: " . $e->getMessage(), [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                throw new \Exception('Error al generar PDF: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            \Log::error('Error en generarPDFConLimpieza: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('Error al generar PDF: ' . $e->getMessage());
        }
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
