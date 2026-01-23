<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarEppDTO;
use App\Models\PedidoProduccion;

/**
 * Use Case para agregar EPP a un pedido
 * 
 * Maneja la creación de registro en pedido_epp
 */
final class AgregarEppUseCase
{
    public function execute(AgregarEppDTO $dto)
    {
        $pedido = PedidoProduccion::findOrFail($dto->pedidoId);

        return $pedido->epps()->create([
            'epp_id' => $dto->eppId,
            'cantidad' => $dto->cantidad,
            'observaciones' => $dto->observaciones,
        ]);
    }
}
