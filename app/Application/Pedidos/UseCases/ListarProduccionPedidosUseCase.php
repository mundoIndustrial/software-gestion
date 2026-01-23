<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;

class ListarProduccionPedidosUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository
    ) {}

    public function ejecutar(ListarProduccionPedidosDTO $dto): array
    {
        // Obtener pedidos aplicando filtros
        $query = $this->pedidoRepository->obtenerTodos();

        // Aplicar filtro de estado si existe
        if (isset($dto->filtros['estado'])) {
            $query = $query->where('estado', $dto->filtros['estado']);
        }

        // Aplicar filtro de búsqueda si existe
        if (isset($dto->filtros['search'])) {
            $searchTerm = $dto->filtros['search'];
            $query = $query->where(function($q) use ($searchTerm) {
                $q->where('numero_pedido', 'like', "%{$searchTerm}%")
                  ->orWhere('cliente', 'like', "%{$searchTerm}%")
                  ->orWhere('descripcion', 'like', "%{$searchTerm}%");
            });
        }

        // Obtener los resultados
        return $query->get()->map(function($pedido) {
            return $pedido; // Aquí podrías transformar a DTOs si necesitas
        })->toArray();
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
