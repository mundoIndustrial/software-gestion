<?php

namespace App\Application\Actions;

use App\Application\DTOs\CrearPrendaDTO;
use App\Application\Services\PrendaServiceNew;
use App\Infrastructure\Jobs\ProcessPrendaImagenesJob;
use App\Models\Prenda;
use Illuminate\Support\Facades\Bus;

class CrearPrendaAction
{
    public function __construct(
        private PrendaServiceNew $prendaService,
    ) {}

    /**
     * Ejecutar la acción de crear prenda
     */
    public function ejecutar(CrearPrendaDTO $dto): Prenda
    {
        \Log::info('Iniciando CrearPrendaAction', [
            'nombre' => $dto->nombre_producto,
            'tipo' => $dto->tipo_prenda,
            'cantidad_fotos' => count($dto->fotos),
        ]);

        try {
            // 1. Crear prenda con variantes y telas
            $prenda = $this->prendaService->crear($dto);

            // 2. Procesar imágenes de forma asincrónica
            if (!empty($dto->fotos)) {
                $this->procesarImagenesAsync($prenda->id, $dto->fotos);
            }

            \Log::info(' CrearPrendaAction completada', [
                'prenda_id' => $prenda->id,
            ]);

            return $prenda;

        } catch (\Exception $e) {
            \Log::error(' Error en CrearPrendaAction', [
                'error' => $e->getMessage(),
                'nombre' => $dto->nombre_producto,
            ]);
            throw $e;
        }
    }

    /**
     * Procesar imágenes de forma asincrónica
     */
    private function procesarImagenesAsync(int $prendaId, array $fotos): void
    {
        try {
            $imagenesData = [];

            foreach ($fotos as $index => $foto) {
                $imagenesData[] = [
                    'archivo' => $foto->archivo,
                    'tipo' => $foto->tipo,
                    'orden' => $index + 1,
                    'ruta_original' => $foto->archivo->getClientOriginalName(),
                ];
            }

            // Despachar job asincrónico
            Bus::dispatch(new ProcessPrendaImagenesJob($prendaId, $imagenesData));

            \Log::info(' Job de procesamiento de imágenes despachado', [
                'prenda_id' => $prendaId,
                'cantidad' => count($imagenesData),
            ]);

        } catch (\Exception $e) {
            \Log::error(' Error despachando job de imágenes', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);
            // No lanzar excepción, solo registrar
        }
    }
}
