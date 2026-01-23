<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\BuscarPedidoPorNumeroDTO;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Illuminate\Support\Facades\Log;

final class BuscarPedidoPorNumeroUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    public function ejecutar(BuscarPedidoPorNumeroDTO $dto)
    {
        Log::info('[BuscarPedidoPorNumeroUseCase] Buscando pedido por número', [
            'numero' => $dto->numero,
        ]);

        if (empty($dto->numero)) {
            throw new \InvalidArgumentException('Número de pedido requerido');
        }

        $pedido = $this->pedidoRepository->obtenerPorNumero($dto->numero);

        Log::info('[BuscarPedidoPorNumeroUseCase] Búsqueda completada', [
            'numero' => $dto->numero,
            'encontrado' => !is_null($pedido),
        ]);

        return $pedido;
    }
}
