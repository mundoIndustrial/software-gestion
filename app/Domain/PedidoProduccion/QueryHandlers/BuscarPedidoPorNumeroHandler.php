<?php

namespace App\Domain\PedidoProduccion\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\PedidoProduccion\Queries\BuscarPedidoPorNumeroQuery;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * BuscarPedidoPorNumeroHandler
 * 
 * Maneja BuscarPedidoPorNumeroQuery
 * Busca un pedido por número único
 */
class BuscarPedidoPorNumeroHandler implements QueryHandler
{
    public function __construct(
        private PedidoProduccion $pedidoModel,
    ) {}

    public function handle(Query $query): mixed
    {
        if (!$query instanceof BuscarPedidoPorNumeroQuery) {
            throw new \InvalidArgumentException('Query debe ser BuscarPedidoPorNumeroQuery');
        }

        try {
            Log::info('🔎 [BuscarPedidoPorNumeroHandler] Buscando pedido', [
                'numero_pedido' => $query->getNumeroPedido(),
            ]);

            $cacheKey = "pedido_numero_{$query->getNumeroPedido()}";
            $pedido = cache()->get($cacheKey);

            if ($pedido) {
                Log::info('💾 [BuscarPedidoPorNumeroHandler] Pedido encontrado en cache');
                return $pedido;
            }

            $pedido = $this->pedidoModel
                ->where('numero_pedido', $query->getNumeroPedido())
                ->with(['prendas', 'logos', 'asesor', 'cliente'])
                ->first();

            if (!$pedido) {
                Log::warning('⚠️ [BuscarPedidoPorNumeroHandler] Pedido no encontrado', [
                    'numero_pedido' => $query->getNumeroPedido(),
                ]);
                return null;
            }

            // Cachear por 1 hora
            cache()->put($cacheKey, $pedido, now()->addHour());

            Log::info(' [BuscarPedidoPorNumeroHandler] Pedido encontrado', [
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            return $pedido;

        } catch (\Exception $e) {
            Log::error(' [BuscarPedidoPorNumeroHandler] Error buscando pedido', [
                'numero_pedido' => $query->getNumeroPedido(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
