<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListarProduccionPedidosUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository
    ) {}

    public function ejecutar(ListarProduccionPedidosDTO $dto): LengthAwarePaginator
    {
        $filtros = $dto->filtros ?? [];

        if ($dto->soloAsesor && $dto->usuarioId) {
            $filtros['asesor_id'] = $dto->usuarioId;
        }

        return $this->pedidoRepository->obtenerPedidosAsesor($filtros);
    }

    /**
     * Obtener estados disponibles para filtros
     */
    public function obtenerEstados(): array
    {
        return [
            'PENDIENTE_SUPERVISOR' => 'Pendiente Supervisor',
            'Pendiente' => 'Pendiente',
            'En Ejecución' => 'En Ejecución',
            'Entregado' => 'Entregado',
            'Anulada' => 'Anulada',
            'No iniciado' => 'No iniciado'
        ];
    }
}

