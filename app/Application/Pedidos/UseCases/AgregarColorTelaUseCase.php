<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarColorTelaDTO;
use App\Models\PrendaPedido;

/**
 * Use Case para agregar combinación color-tela a una prenda
 * 
 * Maneja la creación de registro en prenda_pedido_colores_telas
 */
final class AgregarColorTelaUseCase
{
    public function execute(AgregarColorTelaDTO $dto)
    {
        $prenda = PrendaPedido::findOrFail($dto->prendaId);

        return $prenda->coloresTelas()->create([
            'color_id' => $dto->colorId,
            'tela_id' => $dto->telaId,
        ]);
    }
}
