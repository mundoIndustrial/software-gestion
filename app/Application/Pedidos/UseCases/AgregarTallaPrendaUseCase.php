<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\AgregarTallaPrendaUseCaseContract;

use App\Application\Pedidos\DTOs\AgregarTallaPrendaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PrendaPedido;

/**
 * Use Case para agregar talla y cantidad a una prenda
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * Maneja la creación de registro en prenda_pedido_tallas
 * Antes: 26 lineas | despues: ~17 lineas | Reducción: ~35%
 */
final class AgregarTallaPrendaUseCase implements AgregarTallaPrendaUseCaseContract
{
    use ManejaPedidosUseCase;

    public function execute(AgregarTallaPrendaDTO $dto)
    {
        // CENTRALIZADO: Validar prenda existe (trait)
        $prenda = PrendaPedido::find($dto->prendaId);
        $this->validarObjetoExiste($prenda, 'Prenda', $dto->prendaId);

        return $prenda->tallas()->create([
            'genero' => $dto->genero,
            'talla' => $dto->talla,
            'cantidad' => $dto->cantidad,
        ]);
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {AgregarTallaPrendaUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}




