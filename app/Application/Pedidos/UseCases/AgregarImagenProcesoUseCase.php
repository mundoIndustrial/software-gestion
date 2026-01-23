<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarImagenProcesoDTO;
use App\Models\ProcesosPrendaDetalle;

/**
 * Use Case para agregar imagen de referencia a un proceso de prenda
 * 
 * Maneja la creación de registro en pedidos_procesos_imagenes
 */
final class AgregarImagenProcesoUseCase
{
    public function execute(AgregarImagenProcesoDTO $dto)
    {
        $proceso = ProcesosPrendaDetalle::findOrFail($dto->procesoId);

        return $proceso->imagenes()->create([
            'ruta_original' => $dto->rutaOriginal,
            'ruta_webp' => $dto->rutaWebp ?? $this->generarRutaWebp($dto->rutaOriginal),
            'orden' => $dto->orden,
            'es_principal' => $dto->esPrincipal,
        ]);
    }

    private function generarRutaWebp(string $rutaOriginal): string
    {
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }
}
