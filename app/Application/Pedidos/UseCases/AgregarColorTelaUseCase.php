<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarColorTelaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PrendaPedido;

/**
 * Use Case para agregar combinación color-tela a una prenda
 * 
 * Maneja la creación de registro en prenda_pedido_colores_telas
 */
final class AgregarColorTelaUseCase
{
    use ManejaPedidosUseCase;

    public function execute(AgregarColorTelaDTO $dto)
    {
        $prenda = $this->validarObjetoExiste(
            PrendaPedido::find($dto->prendaId),
            'Prenda',
            $dto->prendaId
        );

        return $prenda->coloresTelas()->create([
            'color_id' => $dto->colorId,
            'tela_id' => $dto->telaId,
        ]);
    }
}

