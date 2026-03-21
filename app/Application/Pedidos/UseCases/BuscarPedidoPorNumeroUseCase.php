<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\BuscarPedidoPorNumeroDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use Illuminate\Support\Facades\Log;

final class BuscarPedidoPorNumeroUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository,
    ) {}

    public function ejecutar(BuscarPedidoPorNumeroDTO $dto)
    {
        Log::info('[BuscarPedidoPorNumeroUseCase] Buscando pedido por numero', [
            'numero' => $dto->numero,
        ]);

        $this->validarNoVacio($dto->numero, 'numero de pedido');

        $pedido = $this->pedidoRepository->obtenerPorNumero($dto->numero);

        Log::info('[BuscarPedidoPorNumeroUseCase] busqueda completada', [
            'numero' => $dto->numero,
            'encontrado' => !is_null($pedido),
        ]);

        return $pedido;
    }
}


