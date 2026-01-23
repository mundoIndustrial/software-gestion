<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarTallaPrendaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PrendaPedido;

/**
 * Use Case para agregar talla y cantidad a una prenda
 * 
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * 
 * Maneja la creaciÃ³n de registro en prenda_pedido_tallas
 * 
 * Antes: 26 lÃ­neas | DespuÃ©s: ~17 lÃ­neas | ReducciÃ³n: ~35%
 */
final class AgregarTallaPrendaUseCase
{
    use ManejaPedidosUseCase;

    public function execute(AgregarTallaPrendaDTO $dto)
    {
        // CENTRALIZADO: Validar prenda existe (trait)
        $prenda = $this->validarObjetoExiste(
            PrendaPedido::find($dto->prendaId),
            "Prenda con ID {$dto->prendaId}"
        );

        return $prenda->tallas()->create([
            'genero' => $dto->genero,
            'talla' => $dto->talla,
            'cantidad' => $dto->cantidad,
        ]);
    }
}

