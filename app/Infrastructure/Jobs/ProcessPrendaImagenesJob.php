<?php

namespace App\Infrastructure\Jobs;

use App\Application\Services\ImagenProcesadorService;
use App\Models\Prenda;
use App\Models\PrendaFoto;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\UploadedFile;

class ProcessPrendaImagenesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300; // 5 minutos
    public int $tries = 3;

    public function __construct(
        private int $prendaId,
        private array $imagenesData,
    ) {}

    /**
     * Ejecutar el job
     */
    public function handle(ImagenProcesadorService $imagenService): void
    {
        try {
            \Log::info('🔄 Iniciando procesamiento de imágenes', [
                'prenda_id' => $this->prendaId,
                'cantidad_imagenes' => count($this->imagenesData),
            ]);

            $prenda = Prenda::findOrFail($this->prendaId);

            foreach ($this->imagenesData as $index => $imagenData) {
                try {
                    // Procesar imagen
                    $ruta = $imagenService->procesarImagen($imagenData['archivo'], $this->prendaId);

                    // Generar miniatura
                    $rutaMiniatura = $imagenService->generarMiniatura($ruta, $this->prendaId);

                    // Obtener información de imagen
                    $info = $imagenService->obtenerInfo($ruta);

                    // Guardar registro en BD
                    PrendaFoto::create([
                        'prenda_id' => $this->prendaId,
                        'ruta_original' => $imagenData['ruta_original'] ?? null,
                        'ruta_webp' => $ruta,
                        'ruta_miniatura' => $rutaMiniatura,
                        'tipo' => $imagenData['tipo'] ?? 'prenda',
                        'orden' => $index + 1,
                        'ancho' => $info['ancho'] ?? null,
                        'alto' => $info['alto'] ?? null,
                        'tamaño' => $info['tamaño'] ?? null,
                    ]);

                    \Log::info(' Imagen procesada', [
                        'prenda_id' => $this->prendaId,
                        'ruta' => $ruta,
                        'orden' => $index + 1,
                    ]);

                } catch (\Exception $e) {
                    \Log::error(' Error procesando imagen individual', [
                        'prenda_id' => $this->prendaId,
                        'indice' => $index,
                        'error' => $e->getMessage(),
                    ]);
                    // Continuar con la siguiente imagen
                }
            }

            \Log::info(' Procesamiento de imágenes completado', [
                'prenda_id' => $this->prendaId,
            ]);

        } catch (\Exception $e) {
            \Log::error(' Error en ProcessPrendaImagenesJob', [
                'prenda_id' => $this->prendaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Manejar fallo del job
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error(' Job ProcessPrendaImagenesJob falló después de ' . $this->tries . ' intentos', [
            'prenda_id' => $this->prendaId,
            'error' => $exception->getMessage(),
        ]);
    }
}
