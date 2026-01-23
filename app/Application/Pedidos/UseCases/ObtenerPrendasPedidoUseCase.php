<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerPrendasPedidoDTO;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Illuminate\Support\Facades\Log;

final class ObtenerPrendasPedidoUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    public function ejecutar(ObtenerPrendasPedidoDTO $dto)
    {
        Log::info('[ObtenerPrendasPedidoUseCase] Obteniendo prendas del pedido', [
            'pedido_id' => $dto->pedidoId,
        ]);

        $pedido = $this->pedidoRepository->obtenerPorId($dto->pedidoId);
        
        if (!$pedido) {
            throw new \InvalidArgumentException("Pedido {$dto->pedidoId} no encontrado");
        }

        $prendas = $pedido->prendas()->get();

        Log::info('[ObtenerPrendasPedidoUseCase] Prendas obtenidas', [
            'pedido_id' => $pedido->id,
            'total_prendas' => $prendas->count(),
        ]);

        return $prendas;
    }
}
