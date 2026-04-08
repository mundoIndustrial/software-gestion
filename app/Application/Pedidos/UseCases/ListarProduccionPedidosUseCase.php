<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as LaravelLengthAwarePaginator;
use Illuminate\Support\Collection;

class ListarProduccionPedidosUseCase
{
    public function __construct(
        private PedidoProduccionReadRepository $pedidoRepository
    ) {}

    public function ejecutar(ListarProduccionPedidosDTO $dto): LengthAwarePaginator
    {
        $filtros = $dto->filtros ?? [];

        if ($dto->soloAsesor && $dto->usuarioId) {
            $filtros['asesor_id'] = $dto->usuarioId;
        }

        $resultado = $this->pedidoRepository->obtenerPedidosAsesor($filtros);

        $paginator = new LaravelLengthAwarePaginator(
            items: new Collection($resultado->items),
            total: $resultado->total,
            perPage: $resultado->perPage,
            currentPage: $resultado->currentPage,
            options: ['path' => $resultado->path]
        );

        if (!empty($resultado->query)) {
            $queryParams = $resultado->query;
            unset($queryParams['page']);

            if (!empty($queryParams)) {
                $paginator->appends($queryParams);
            }
        }

        return $paginator;
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
