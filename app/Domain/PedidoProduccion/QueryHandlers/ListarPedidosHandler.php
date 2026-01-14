<?php

namespace App\Domain\PedidoProduccion\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\PedidoProduccion\Queries\ListarPedidosQuery;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * ListarPedidosHandler
 * 
 * Maneja ListarPedidosQuery
 * Lista pedidos con paginación
 */
class ListarPedidosHandler implements QueryHandler
{
    public function __construct(
        private PedidoProduccion $pedidoModel,
    ) {}

    public function handle(Query $query): mixed
    {
        if (!$query instanceof ListarPedidosQuery) {
            throw new \InvalidArgumentException('Query debe ser ListarPedidosQuery');
        }

        try {
            Log::info('📋 [ListarPedidosHandler] Listando pedidos', [
                'page' => $query->getPage(),
                'per_page' => $query->getPerPage(),
            ]);

            $pedidos = $this->pedidoModel
                ->with(['asesor', 'cliente'])
                ->orderBy($query->getOrdenar(), $query->getDireccion())
                ->paginate($query->getPerPage(), ['*'], 'page', $query->getPage());

            Log::info('✅ [ListarPedidosHandler] Pedidos listados', [
                'total' => $pedidos->total(),
                'page' => $pedidos->currentPage(),
            ]);

            return $pedidos;

        } catch (\Exception $e) {
            Log::error('❌ [ListarPedidosHandler] Error listando pedidos', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
