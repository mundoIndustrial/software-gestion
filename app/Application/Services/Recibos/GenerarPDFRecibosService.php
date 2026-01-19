<?php

namespace App\Application\Services\Recibos;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerarPDFRecibosService
{
    /**
     * Generar PDF de un recibo
     * 
     * @param array $datosRecibo
     * @param int $pedidoId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \Exception
     */
    public function generarPDF(array $datosRecibo, int $pedidoId)
    {
        Log::info('📄 [PDF] Generando PDF para recibo: ' . $pedidoId);

        try {
            // Importar dependencia (barryvdh/laravel-dompdf o similar)
            // Si no está instalada, retornar datos para descarga del frontend
            
            if (!class_exists('\PDF')) {
                Log::warning('⚠️ [PDF] Librería PDF no instalada, retornando datos');
                return $this->generarDatosPDF($datosRecibo, $pedidoId);
            }

            // Generar PDF con la vista
            $pdf = \PDF::loadView('asesores.recibos.pdf', [
                'datos' => $datosRecibo,
                'pedido_id' => $pedidoId,
                'fecha_generacion' => now()->format('d/m/Y H:i'),
            ]);

            Log::info('✅ [PDF] PDF generado correctamente', [
                'pedido_id' => $pedidoId,
                'tamaño' => strlen($pdf->output())
            ]);

            return $pdf->download('recibo_' . $pedidoId . '.pdf');

        } catch (\Exception $e) {
            Log::error('❌ [PDF] Error generando PDF', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generar datos para PDF en el frontend
     */
    private function generarDatosPDF(array $datosRecibo, int $pedidoId): array
    {
        Log::info('📊 [PDF-DATOS] Preparando datos para PDF frontend');

        $datos = [
            'success' => true,
            'message' => 'Datos de PDF listos para descargar',
            'pedido_id' => $pedidoId,
            'recibo' => $datosRecibo,
            'metadata' => [
                'generado_en' => now()->format('d/m/Y H:i:s'),
                'cliente' => $datosRecibo['cliente'] ?? 'Sin especificar',
                'numero_pedido' => $datosRecibo['numero_pedido'] ?? 'N/A',
                'prendas_count' => count($datosRecibo['prendas'] ?? []),
            ]
        ];

        Log::info('✅ [PDF-DATOS] Datos preparados correctamente');

        return $datos;
    }

    /**
     * Guardar PDF en storage
     */
    public function guardarPDF(array $datosRecibo, int $pedidoId, string $disco = 'local'): string
    {
        Log::info('💾 [PDF-GUARDAR] Guardando PDF en storage');

        try {
            $nombreArchivo = 'recibos/recibo_' . $pedidoId . '_' . now()->format('YmdHis') . '.pdf';

            // Si PDF no está disponible, guardar JSON
            if (!class_exists('\PDF')) {
                $contenido = json_encode($datosRecibo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                Storage::disk($disco)->put($nombreArchivo . '.json', $contenido);
                Log::info('💾 [PDF-GUARDAR] Datos guardados como JSON', ['ruta' => $nombreArchivo]);
                return $nombreArchivo . '.json';
            }

            $pdf = \PDF::loadView('asesores.recibos.pdf', [
                'datos' => $datosRecibo,
                'pedido_id' => $pedidoId,
                'fecha_generacion' => now()->format('d/m/Y H:i'),
            ]);

            Storage::disk($disco)->put($nombreArchivo, $pdf->output());

            Log::info('✅ [PDF-GUARDAR] PDF guardado correctamente', [
                'ruta' => $nombreArchivo,
                'tamaño' => strlen($pdf->output())
            ]);

            return $nombreArchivo;

        } catch (\Exception $e) {
            Log::error('❌ [PDF-GUARDAR] Error guardando PDF', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener PDF guardado
     */
    public function obtenerPDFGuardado(string $rutaArchivo, string $disco = 'local'): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        Log::info('📥 [PDF-OBTENER] Obteniendo PDF: ' . $rutaArchivo);

        try {
            if (!Storage::disk($disco)->exists($rutaArchivo)) {
                throw new \Exception('Archivo PDF no encontrado', 404);
            }

            Log::info('✅ [PDF-OBTENER] PDF encontrado');

            return Storage::disk($disco)->download($rutaArchivo);

        } catch (\Exception $e) {
            Log::error('❌ [PDF-OBTENER] Error obteniendo PDF', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generar vista previa de HTML del recibo (para visualización)
     */
    public function generarVistaPreviaHTML(array $datosRecibo, int $pedidoId): string
    {
        Log::info('🎨 [VISTA-PREVIA] Generando HTML de recibo');

        try {
            $html = view('asesores.recibos.pdf', [
                'datos' => $datosRecibo,
                'pedido_id' => $pedidoId,
                'fecha_generacion' => now()->format('d/m/Y H:i'),
            ])->render();

            Log::info('✅ [VISTA-PREVIA] HTML generado', ['tamaño' => strlen($html)]);

            return $html;

        } catch (\Exception $e) {
            Log::error('❌ [VISTA-PREVIA] Error generando HTML', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Enviar recibo por email (futuro)
     */
    public function enviarPorEmail(array $datosRecibo, int $pedidoId, string $emailDestino): bool
    {
        Log::info('📧 [EMAIL] Preparando envío de recibo', [
            'pedido_id' => $pedidoId,
            'email' => $emailDestino
        ]);

        try {
            // TODO: Implementar con Mailable
            // Mail::send(new ReciboMail($datosRecibo, $pedidoId));

            Log::warning('⚠️ [EMAIL] Envío por email no implementado aún');

            return false;

        } catch (\Exception $e) {
            Log::error('❌ [EMAIL] Error enviando email', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
