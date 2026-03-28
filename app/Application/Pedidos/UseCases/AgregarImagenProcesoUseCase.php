<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\AgregarImagenProcesoUseCaseContract;

use App\Application\Pedidos\DTOs\AgregarImagenProcesoDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\ProcesosPrendaDetalle;

/**
 * Use Case para agregar imagen de referencia a un proceso de prenda
 * 
 * Maneja la creación de registro en pedidos_procesos_imagenes
 */
final class AgregarImagenProcesoUseCase implements AgregarImagenProcesoUseCaseContract
{
    use ManejaPedidosUseCase;

    public function execute(AgregarImagenProcesoDTO $dto)
    {
        $proceso = $this->validarObjetoExiste(
            ProcesosPrendaDetalle::find($dto->procesoId),
            'Proceso',
            $dto->procesoId
        );

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

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {AgregarImagenProcesoUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}





