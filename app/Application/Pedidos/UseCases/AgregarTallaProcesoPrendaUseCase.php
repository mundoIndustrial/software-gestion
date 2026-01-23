<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarTallaProcesoPrendaDTO;
use App\Models\ProcesosPrendaDetalle;

/**
 * Use Case para agregar talla a un proceso de prenda
 * 
 * Maneja la creación de registro en pedidos_procesos_prenda_tallas
 * que contiene el desglose de cantidades por talla para cada proceso
 */
final class AgregarTallaProcesoPrendaUseCase
{
    public function execute(AgregarTallaProcesoPrendaDTO $dto)
    {
        $proceso = ProcesosPrendaDetalle::findOrFail($dto->procesoId);

        return $proceso->tallas()->create([
            'genero' => $dto->genero,
            'talla' => $dto->talla,
            'cantidad' => $dto->cantidad,
        ]);
    }
}
