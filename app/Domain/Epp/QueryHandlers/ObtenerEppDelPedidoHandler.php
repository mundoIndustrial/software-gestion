<?php

namespace App\Domain\Epp\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\Epp\Queries\ObtenerEppDelPedidoQuery;
use App\Domain\Epp\Repositories\PedidoEppRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerEppDelPedidoHandler
 * 
 * Maneja ObtenerEppDelPedidoQuery
 * Obtiene todos los EPP agregados a un pedido
 */
class ObtenerEppDelPedidoHandler implements QueryHandler
{
    public function __construct(
        private PedidoEppRepositoryInterface $pedidoEppRepository,
    ) {}

    public function handle(Query $query): mixed
    {
        if (!$query instanceof ObtenerEppDelPedidoQuery) {
            throw new \InvalidArgumentException('Query debe ser ObtenerEppDelPedidoQuery');
        }

        try {
            Log::info('🔍 [ObtenerEppDelPedidoHandler] Obteniendo EPP del pedido', [
                'pedido_id' => $query->getPedidoId(),
            ]);

            $epps = $this->pedidoEppRepository->obtenerEppDelPedido($query->getPedidoId());

            Log::info(' [ObtenerEppDelPedidoHandler] EPP obtenidos', [
                'pedido_id' => $query->getPedidoId(),
                'cantidad' => count($epps),
            ]);

            return $epps;

        } catch (\Exception $e) {
            Log::error(' [ObtenerEppDelPedidoHandler] Error obteniendo EPP', [
                'error' => $e->getMessage(),
                'pedido_id' => $query->getPedidoId(),
            ]);

            throw $e;
        }
    }
}
