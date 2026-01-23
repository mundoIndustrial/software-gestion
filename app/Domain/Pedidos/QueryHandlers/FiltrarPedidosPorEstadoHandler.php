<?php

namespace App\Domain\Pedidos\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\Pedidos\Queries\FiltrarPedidosPorEstadoQuery;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * FiltrarPedidosPorEstadoHandler
 * 
 * Maneja FiltrarPedidosPorEstadoQuery
 * Filtra pedidos por estado especÃ­fico
 */
class FiltrarPedidosPorEstadoHandler implements QueryHandler
{
    public function __construct(
        private PedidoProduccion $pedidoModel,
    ) {}

    public function handle(Query $query): mixed
    {
        if (!$query instanceof FiltrarPedidosPorEstadoQuery) {
            throw new \InvalidArgumentException('Query debe ser FiltrarPedidosPorEstadoQuery');
        }

        try {
            Log::info(' [FiltrarPedidosPorEstadoHandler] Filtrando pedidos', [
                'estado' => $query->getEstado(),
                'page' => $query->getPage(),
            ]);

            $pedidos = $this->pedidoModel
                ->where('estado', $query->getEstado())
                ->with(['asesor', 'cliente'])
                ->orderBy('created_at', 'desc')
                ->paginate($query->getPerPage(), ['*'], 'page', $query->getPage());

            Log::info(' [FiltrarPedidosPorEstadoHandler] Pedidos filtrados', [
                'estado' => $query->getEstado(),
                'total' => $pedidos->total(),
            ]);

            return $pedidos;

        } catch (\Exception $e) {
            Log::error(' [FiltrarPedidosPorEstadoHandler] Error filtrando pedidos', [
                'estado' => $query->getEstado(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

