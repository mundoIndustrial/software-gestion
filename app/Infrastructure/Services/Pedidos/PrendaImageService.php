<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PrendaPedido;

/**
 * Maneja la persistencia de fotos de una prenda agregada manualmente.
 */
class PrendaImageService
{
    public function guardarFotos(PrendaPedido $prenda, ?array $imagenes, ?array $imagenesExistentes): void
    {
        $fotos = [];

        if (!empty($imagenes)) {
            foreach ($imagenes as $orden => $rutaOriginal) {
                $fotos[$rutaOriginal] = [
                    'ruta_original' => $rutaOriginal,
                    'ruta_webp' => $this->generarRutaWebp($rutaOriginal),
                    'orden' => $orden + 1,
                ];
            }
        }

        if (!empty($imagenesExistentes)) {
            foreach ($imagenesExistentes as $imagenExistente) {
                if (is_array($imagenExistente) && isset($imagenExistente['previewUrl'])) {
                    $ruta = $imagenExistente['previewUrl'];

                    if (!isset($fotos[$ruta])) {
                        $fotos[$ruta] = [
                            'ruta_original' => $ruta,
                            'ruta_webp' => $this->generarRutaWebp($ruta),
                            'orden' => count($fotos) + 1,
                        ];
                    }
                }
            }
        }

        if (empty($fotos)) {
            return;
        }

        foreach ($fotos as $datosFoto) {
            $prenda->fotos()->create($datosFoto);
        }
    }

    private function generarRutaWebp(string $rutaOriginal): string
    {
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }
}
