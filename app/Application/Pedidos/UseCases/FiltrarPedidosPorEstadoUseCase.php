<?php

namespace App\Application\Pedidos\UseCases;

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
        $this->validarPositivo($dto->page, 'pagina');

        $pedidos = $this->pedidoRepository->porEstado($dto->estado);

        if (is_object($pedidos) && method_exists($pedidos, 'total')) {
            $total = $pedidos->total();
        } elseif (is_countable($pedidos)) {
            $total = count($pedidos);
        } else {
            $total = 0;
        }

        Log::info('[FiltrarPedidosPorEstadoUseCase] Filtrado completado', [
            'estado' => $dto->estado,
            'total' => $total,
        ]);

        return $pedidos;
    }
}
