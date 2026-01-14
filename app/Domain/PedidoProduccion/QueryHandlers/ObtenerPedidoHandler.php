<?php

namespace App\Domain\PedidoProduccion\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\PedidoProduccion\Queries\ObtenerPedidoQuery;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerPedidoHandler
 * 
 * Maneja la query ObtenerPedidoQuery
 * Obtiene detalles completos del pedido incluyendo prendas y logos
 * 
 * Responsabilidades:
 * - Obtener pedido de la base de datos
 * - Cargar relaciones (prendas, logos, etc)
 * - Cachear resultado si es necesario
 * - Retornar el pedido o null si no existe
 */
class ObtenerPedidoHandler implements QueryHandler
{
    public function __construct(
        private PedidoProduccion $pedidoModel,
    ) {}

    /**
     * Ejecutar la query
     * 
     * @param ObtenerPedidoQuery $query
     * @return PedidoProduccion|null
     */
    public function handle(Query $query): mixed
    {
        if (!$query instanceof ObtenerPedidoQuery) {
            throw new \InvalidArgumentException('Query debe ser ObtenerPedidoQuery');
        }

        try {
            Log::info('🔍 [ObtenerPedidoHandler] Buscando pedido', [
                'pedido_id' => $query->getPedidoId(),
            ]);

            // Obtener del cache primero
            $cacheKey = "pedido_{$query->getPedidoId()}_completo";
            $pedido = cache()->get($cacheKey);

            if ($pedido) {
                Log::info('💾 [ObtenerPedidoHandler] Pedido obtenido del cache', [
                    'pedido_id' => $query->getPedidoId(),
                ]);
                return $pedido;
            }

            // Obtener de la base de datos con relaciones
            $pedido = $this->pedidoModel
                ->where('id', $query->getPedidoId())
                ->with(['prendas', 'logos', 'asesor', 'cliente'])
                ->first();

            if (!$pedido) {
                Log::warning('⚠️ [ObtenerPedidoHandler] Pedido no encontrado', [
                    'pedido_id' => $query->getPedidoId(),
                ]);
                return null;
            }

            // Cachear por 1 hora
            cache()->put($cacheKey, $pedido, now()->addHour());

            Log::info('✅ [ObtenerPedidoHandler] Pedido obtenido de BD y cacheado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'prendas_count' => $pedido->prendas?->count() ?? 0,
                'logos_count' => $pedido->logos?->count() ?? 0,
            ]);

            return $pedido;

        } catch (\Exception $e) {
            Log::error('❌ [ObtenerPedidoHandler] Error obteniendo pedido', [
                'pedido_id' => $query->getPedidoId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
