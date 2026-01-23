<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\Catalogs\EstadoPedidoCatalog;
use App\Application\Pedidos\DTOs\FiltrarPedidosPorEstadoDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use Illuminate\Support\Facades\Log;

final class FiltrarPedidosPorEstadoUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository,
    ) {}

    public function ejecutar(FiltrarPedidosPorEstadoDTO $dto)
    {
        Log::info('[FiltrarPedidosPorEstadoUseCase] Filtrando pedidos por estado', [
            'estado' => $dto->estado,
            'page' => $dto->page,
        ]);

        $this->validarEstadoValido($dto->estado);
        $this->validarPositivo($dto->page, 'PÃ¡gina');

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


