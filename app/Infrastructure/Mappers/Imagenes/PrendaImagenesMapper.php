<?php

namespace App\Infrastructure\Mappers\Imagenes;

use App\Domain\Pedidos\ValueObjects\ImagenPrenda;
use Illuminate\Support\Facades\Log;

/**
 * PrendaImagenesMapper
 * 
 * Responsabilidad: Orquestar el mapeo de todas las imágenes de una prenda
 * 
 * Entrada: array crudo del frontend
 * Salida: array formateado listo para guardar en BD
 * 
 * Flujo:
 * 1. Recibe array de imágenes del frontend
 * 2. Convierte cada una a ImagenPrenda (VO) → valida y estructura
 * 3. Usa ImagenDTOToPrendaArrayMapper para convertir a array
 * 4. Retorna array listo para BD
 * 
 * Beneficio: Separación clara de responsabilidades
 * - Este mapper: orquestación
 * - VO: validación y estructura
 * - DTOToArrayMapper: transformación simple
 */
final class PrendaImagenesMapper
{
    public function __construct(
        private ImagenDTOToPrendaArrayMapper $dtoToArrayMapper,
    ) {}

    /**
     * Mapear array de imágenes de prenda → array formateado
     * 
     * @param array $imagenes - Array crudo del frontend
     * @return array - Array formateado para guardar en BD
     * 
     * Ejemplo entrada:
     * [
     *   {previewUrl: "blob:...", nombre: "roja.png", tamano: 2048},
     *   {file: UploadedFile, nombre: "azul.png"},
     *   "/storage/existing.webp"
     * ]
     * 
     * Ejemplo salida:
     * [
     *   {ruta_original: "roja.png", ruta_webp: "blob:...", orden: 1, tamano: 2048},
     *   {ruta_original: "azul.png", ruta_webp: null, orden: 2},
     *   {ruta_original: "existing.webp", ruta_webp: "/storage/existing.webp", orden: 3}
     * ]
     */
    public function mapear(array $imagenes): array
    {
        if (empty($imagenes)) {
            Log::info('[PrendaImagenesMapper] No hay imágenes para mapear');
            return [];
        }

        Log::info('[PrendaImagenesMapper] Iniciando mapeo', [
            'cantidad_imagenes' => count($imagenes),
        ]);

        $fotosFormateadas = [];

        foreach ($imagenes as $idx => $imagen) {
            try {
                // 1. Validar y crear VO (ImagenPrenda.from() valida)
                $imagenVO = ImagenPrenda::from($imagen, $idx + 1);

                // 2. Transformar VO → array
                $fotoFormateada = $this->dtoToArrayMapper->mapear($imagenVO);

                $fotosFormateadas[] = $fotoFormateada;

                Log::debug('[PrendaImagenesMapper] Imagen procesada', [
                    'indice' => $idx,
                    'nombre' => $imagenVO->nombre,
                    'orden' => $imagenVO->orden,
                ]);

            } catch (\InvalidArgumentException $e) {
                Log::warning('[PrendaImagenesMapper] Imagen inválida, ignorando', [
                    'indice' => $idx,
                    'error' => $e->getMessage(),
                    'imagen_data' => is_array($imagen) ? json_encode($imagen) : gettype($imagen),
                ]);
                // Continuar con próxima imagen
                continue;
            }
        }

        Log::info('[PrendaImagenesMapper] Mapeo completado', [
            'cantidad_original' => count($imagenes),
            'cantidad_procesada' => count($fotosFormateadas),
            'descartadas' => count($imagenes) - count($fotosFormateadas),
        ]);

        return $fotosFormateadas;
    }
}
