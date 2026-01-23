<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarTallaPrendaDTO;
use App\Models\PrendaPedido;

/**
 * Use Case para agregar talla y cantidad a una prenda
 * 
 * Maneja la creación de registro en prenda_pedido_tallas
 */
final class AgregarTallaPrendaUseCase
{
    public function execute(AgregarTallaPrendaDTO $dto)
    {
        $prenda = PrendaPedido::findOrFail($dto->prendaId);

        return $prenda->tallas()->create([
            'genero' => $dto->genero,
            'talla' => $dto->talla,
            'cantidad' => $dto->cantidad,
        ]);
    }
}
