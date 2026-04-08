<?php

namespace App\Application\Asesores\UseCases;

use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as LaravelLengthAwarePaginator;
use Illuminate\Support\Collection;

final class ListarBorradoresAsesorUseCase
{
    public function __construct(
        private PedidoProduccionReadRepository $pedidoRepository
    ) {}

    public function ejecutar(int $asesorId, int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        $resultado = $this->pedidoRepository->obtenerPedidosAsesor([
            'asesor_id' => $asesorId,
            'estado' => 'Borrador',
            'sin_numero' => true,
            'page' => max(1, $page),
            'per_page' => max(1, $perPage),
        ]);

        return new LaravelLengthAwarePaginator(
            items: new Collection($resultado->items),
            total: $resultado->total,
            perPage: $resultado->perPage,
            currentPage: $resultado->currentPage,
            options: ['path' => $resultado->path]
        );
    }
}
