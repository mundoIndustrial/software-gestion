<?php

namespace App\Infrastructure\Mappers\Imagenes;

use App\Domain\Pedidos\ValueObjects\ImagenTela;
use Illuminate\Support\Facades\Log;

/**
 * ImagenDTOToTelaArrayMapper
 * 
 * Responsabilidad ÚNICA: Convertir ImagenTela (VO) → array para BD
 */
final class ImagenDTOToTelaArrayMapper
{
    /**
     * Mapear un VO individual ImagenTela → array
     */
    public function mapear(ImagenTela $imagen): array
    {
        $array = $imagen->toArray();

        Log::debug('[ImagenDTOToTelaArrayMapper] Imagen de tela mapeada', [
            'nombre' => $imagen->nombre,
            'tipo' => $this->tipoImagen($imagen),
            'orden' => $imagen->orden,
        ]);

        return $array;
    }

    /**
     * Mapear una colección de VO → array
     */
    public function mapearMultiples(array $imagenesVO): array
    {
        return array_map(
            fn(ImagenTela $img) => $this->mapear($img),
            $imagenesVO
        );
    }

    /**
     * Helper
     */
    private function tipoImagen(ImagenTela $imagen): string
    {
        if ($imagen->esPreview()) {
            return 'preview';
        }
        if ($imagen->esArchivo()) {
            return 'archivo';
        }
        if ($imagen->esRutaAlmacenada()) {
            return 'ruta_almacenada';
        }
        return 'desconocido';
    }
}
