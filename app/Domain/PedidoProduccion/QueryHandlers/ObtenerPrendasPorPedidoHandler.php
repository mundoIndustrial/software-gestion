<?php

namespace App\Domain\PedidoProduccion\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\PedidoProduccion\Queries\ObtenerPrendasPorPedidoQuery;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerPrendasPorPedidoHandler
 * 
 * Maneja ObtenerPrendasPorPedidoQuery
 * Obtiene todas las prendas de un pedido con detalles
 */
class ObtenerPrendasPorPedidoHandler implements QueryHandler
{
    public function __construct(
        private PrendaPedido $prendaModel,
    ) {}

    public function handle(Query $query): mixed
    {
        if (!$query instanceof ObtenerPrendasPorPedidoQuery) {
            throw new \InvalidArgumentException('Query debe ser ObtenerPrendasPorPedidoQuery');
        }

        try {
            Log::info(' [ObtenerPrendasPorPedidoHandler] Obteniendo prendas', [
                'pedido_id' => $query->getPedidoId(),
            ]);

            $cacheKey = "pedido_{$query->getPedidoId()}_prendas";
            $prendas = cache()->get($cacheKey);

            if ($prendas) {
                Log::info('💾 [ObtenerPrendasPorPedidoHandler] Prendas obtenidas del cache');
                return $prendas;
            }

            $prendas = $this->prendaModel
                ->where('pedido_id', $query->getPedidoId())
                ->with(['color', 'tela', 'tipoManga', 'tipoBroche', 'tallas'])
                ->get();

            // Cachear por 1 hora
            cache()->put($cacheKey, $prendas, now()->addHour());

            Log::info(' [ObtenerPrendasPorPedidoHandler] Prendas obtenidas', [
                'pedido_id' => $query->getPedidoId(),
                'cantidad' => $prendas->count(),
            ]);

            return $prendas;

        } catch (\Exception $e) {
            Log::error(' [ObtenerPrendasPorPedidoHandler] Error obteniendo prendas', [
                'pedido_id' => $query->getPedidoId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
