<?php

namespace App\Infrastructure\Mappers\Imagenes;

use App\Domain\Pedidos\ValueObjects\ImagenPrenda;
use Illuminate\Support\Facades\Log;

/**
 * ImagenDTOToPrendaArrayMapper
 * 
 * Responsabilidad ÚNICA: Convertir ImagenPrenda (VO) → array para BD
 * 
 * Principio: Single Responsibility
 * - NO valida
 * - NO determina formato
 * - Solo transforma
 * 
 * Ejemplo:
 * ```
 * $imagenVO = ImagenPrenda::fromPreviewUrl([...], 1);
 * $array = mapper.mapear($imagenVO);
 * // Result: ['ruta_original' => '...', 'ruta_webp' => '...', ...]
 * ```
 */
final class ImagenDTOToPrendaArrayMapper
{
    /**
     * Mapear un VO individual ImagenPrenda → array
     */
    public function mapear(ImagenPrenda $imagen): array
    {
        // El VO es responsable de convertirse a array
        // El mapper solo orquesta el formato esperado por la BD
        $array = $imagen->toArray();

        Log::debug('[ImagenDTOToPrendaArrayMapper] Imagen mapeada', [
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
            fn(ImagenPrenda $img) => $this->mapear($img),
            $imagenesVO
        );
    }

    /**
     * Helper: Determinar tipo de imagen para logging
     */
    private function tipoImagen(ImagenPrenda $imagen): string
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
