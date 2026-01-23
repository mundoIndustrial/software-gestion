<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\BuscarPedidoPorNumeroDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Illuminate\Support\Facades\Log;

final class BuscarPedidoPorNumeroUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    public function ejecutar(BuscarPedidoPorNumeroDTO $dto)
    {
        Log::info('[BuscarPedidoPorNumeroUseCase] Buscando pedido por número', [
            'numero' => $dto->numero,
        ]);

        $this->validarNoVacio($dto->numero, 'Número de pedido');

        $pedido = $this->pedidoRepository->obtenerPorNumero($dto->numero);

        Log::info('[BuscarPedidoPorNumeroUseCase] Búsqueda completada', [
            'numero' => $dto->numero,
            'encontrado' => !is_null($pedido),
        ]);

        return $pedido;
    }
}
