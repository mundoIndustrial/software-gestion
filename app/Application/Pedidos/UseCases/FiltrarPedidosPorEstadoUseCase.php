<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\FiltrarPedidosPorEstadoDTO;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Illuminate\Support\Facades\Log;

final class FiltrarPedidosPorEstadoUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    public function ejecutar(FiltrarPedidosPorEstadoDTO $dto)
    {
        Log::info('[FiltrarPedidosPorEstadoUseCase] Filtrando pedidos por estado', [
            'estado' => $dto->estado,
            'page' => $dto->page,
        ]);

        $estadosValidos = ['activo', 'pendiente', 'completado', 'cancelado'];
        if (!in_array($dto->estado, $estadosValidos)) {
            throw new \InvalidArgumentException("Estado '{$dto->estado}' no válido");
        }

        $pedidos = $this->pedidoRepository->obtenerPorEstado(
            $dto->estado,
            $dto->page,
            $dto->perPage
        );

        Log::info('[FiltrarPedidosPorEstadoUseCase] Filtrado completado', [
            'estado' => $dto->estado,
            'total' => $pedidos->total() ?? count($pedidos),
        ]);

        return $pedidos;
    }
}
