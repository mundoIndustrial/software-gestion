<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarTallaProcesoPrendaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\ProcesosPrendaDetalle;

/**
 * Use Case para agregar talla a un proceso de prenda
 * 
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * 
 * Maneja la creación de registro en pedidos_procesos_prenda_tallas
 * que contiene el desglose de cantidades por talla para cada proceso
 * 
 * Antes: 20 líneas | Después: ~15 líneas | Reducción: ~25%
 */
final class AgregarTallaProcesoPrendaUseCase
{
    use ManejaPedidosUseCase;

    public function execute(AgregarTallaProcesoPrendaDTO $dto)
    {
        // CENTRALIZADO: Validar proceso existe (trait)
        $proceso = $this->validarObjetoExiste(
            ProcesosPrendaDetalle::find($dto->procesoId),
            "Proceso con ID {$dto->procesoId}"
        );

        return $proceso->tallas()->create([
            'genero' => $dto->genero,
            'talla' => $dto->talla,
            'cantidad' => $dto->cantidad,
        ]);
    }
}
