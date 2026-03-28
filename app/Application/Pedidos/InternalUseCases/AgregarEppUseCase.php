<?php

namespace App\Application\Pedidos\InternalUseCases;

use App\Domain\Pedidos\UseCases\AgregarEppUseCaseContract;

use App\Application\Pedidos\DTOs\AgregarEppDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PedidoProduccion;

/**
 * Use Case para agregar EPP a un pedido
 * 
 * Maneja la creación de registro en pedido_epp
 */
final class AgregarEppUseCase implements AgregarEppUseCaseContract
{
    use ManejaPedidosUseCase;

    public function execute(AgregarEppDTO $dto)
    {
        $pedido = $this->validarObjetoExiste(
            PedidoProduccion::find($dto->pedidoId),
            'Pedido',
            $dto->pedidoId
        );

        return $pedido->epps()->create([
            'epp_id' => $dto->eppId,
            'cantidad' => $dto->cantidad,
            'observaciones' => $dto->observaciones,
        ]);
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {AgregarEppUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}


