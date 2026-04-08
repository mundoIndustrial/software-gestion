<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Infrastructure\Mappers\Imagenes\PrendaImagenesMapper;
use App\Infrastructure\Mappers\Imagenes\TelaImagenesMapper;
use Illuminate\Support\Facades\Log;

/**
 * ImagenesService - FACHADA PÚBLICA
 * 
 * Responsabilidad: Proporcionar interfaz pública simple para mapear imágenes
 * 
 * Encapsula los mappers interiores para que el resto de la aplicación
 * no necesite conocer la complejidad interna.
 * 
 * Patrón: Facade + Dependency Injection
 * 
 * Uso en Application/Controllers:
 * ```php
 * $imagenesService->mapearImagenesPrenda($item);
 * $imagenesService->mapearImagenesTelas($telas);
 * ```
 */
final class ImagenesService
{
    public function __construct(
        private PrendaImagenesMapper $prendaMapper,
        private TelaImagenesMapper $telaMapper,
    ) {}

    /**
     * Mapear imágenes de prenda
     * 
     * @param array $item - Item de prenda con field 'imagenes'
     * @return array - Imágenes formateadas para guardar
     */
    public function mapearImagenesPrenda(array $item): array
    {
        $imagenes = $item['imagenes'] ?? [];

        if (!is_array($imagenes)) {
            Log::warning('[ImagenesService] imagenes no es array', [
                'tipo' => gettype($imagenes),
            ]);
            return [];
        }

        return $this->prendaMapper->mapear($imagenes);
    }

    /**
     * Mapear imágenes de telas
     * 
     * @param array $telas - Array de telas del frontend
     * @return array - Telas con imágenes formateadas
     */
    public function mapearImagenesTelas(array $telas): array
    {
        if (!is_array($telas)) {
            Log::warning('[ImagenesService] telas no es array', [
                'tipo' => gettype($telas),
            ]);
            return [];
        }

        return $this->telaMapper->mapear($telas);
    }
}
