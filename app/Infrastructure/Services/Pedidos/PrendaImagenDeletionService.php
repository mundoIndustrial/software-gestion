<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PrendaFotoPedido;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrendaImagen;

final class PrendaImagenDeletionService
{
    public function __construct(
        private readonly ImagenService $imagenService,
    ) {
    }

    /**
     * Elimina imágenes de prenda/proceso tanto en BD como en storage.
     *
     * @param array<int, array<string, mixed>> $imagenesAEliminar
     */
    public function eliminarImagenes(PrendaPedido $prenda, array $imagenesAEliminar): void
    {
        if (empty($imagenesAEliminar)) {
            return;
        }

        \Log::info('[PrendaImagenDeletionService] Iniciando eliminación de imágenes', [
            'prenda_id' => $prenda->id,
            'cantidad' => count($imagenesAEliminar),
        ]);

        $imagenesProcesadas = 0;
        $imagenesError = 0;

        foreach ($imagenesAEliminar as $imagen) {
            try {
                $imagenId = $imagen['id'] ?? $imagen['prenda_foto_id'] ?? null;
                $rutaOriginal = $imagen['ruta_original'] ?? null;
                $rutaWebp = $imagen['ruta_webp'] ?? null;

                if (!$imagenId) {
                    \Log::warning('[PrendaImagenDeletionService] Imagen sin ID para eliminar', [
                        'imagen_data' => $imagen,
                    ]);
                    continue;
                }

                $foto = ProcesoPrendaImagen::where('id', $imagenId)->first();
                if (!$foto) {
                    $foto = PrendaFotoPedido::where('id', $imagenId)
                        ->where('prenda_pedido_id', $prenda->id)
                        ->first();
                }

                if (!$foto) {
                    \Log::warning('[PrendaImagenDeletionService] Imagen no encontrada en BD', [
                        'imagen_id' => $imagenId,
                        'prenda_id' => $prenda->id,
                    ]);
                    continue;
                }

                $foto->delete();
                $imagenesProcesadas++;

                if ($rutaOriginal) {
                    $this->imagenService->eliminarImagen($rutaOriginal);
                }

                if ($rutaWebp && $rutaWebp !== $rutaOriginal) {
                    $this->imagenService->eliminarImagen($rutaWebp);
                }
            } catch (\Throwable $e) {
                $imagenesError++;
                \Log::error('[PrendaImagenDeletionService] Error eliminando imagen', [
                    'imagen_id' => $imagen['id'] ?? 'UNKNOWN',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        \Log::info('[PrendaImagenDeletionService] Eliminación de imágenes completada', [
            'prenda_id' => $prenda->id,
            'procesadas' => $imagenesProcesadas,
            'errores' => $imagenesError,
            'total' => count($imagenesAEliminar),
        ]);
    }
}

