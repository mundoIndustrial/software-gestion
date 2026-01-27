<?php

namespace App\Domain\Pedidos\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\Pedidos\Queries\ObtenerPedidoQuery;
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
            Log::info(' [ObtenerPedidoHandler] Buscando pedido', [
                'pedido_id' => $query->getPedidoId(),
            ]);

            // ðŸ”„ NO USAR CACHE - Las relaciones (fotos, variantes, etc) pueden cambiar frecuentemente
            // Si necesitas cache, considera invalidarlo cuando se actualiza un pedido o prenda

            // ðŸ”„ NO USAR CACHE - Las relaciones (fotos, variantes, etc) pueden cambiar frecuentemente
            // Si necesitas cache, considera invalidarlo cuando se actualiza un pedido o prenda
            $pedido = $this->pedidoModel
                ->where('id', $query->getPedidoId())
                ->with([
                    'prendas',                          //  Prendas del pedido
                    'prendas.variantes',                //  Variantes (manga, broche)
                    'prendas.tallas',                   //  Tallas por gÃ©nero
                    'prendas.coloresTelas',             //  Combinaciones color-tela
                    'prendas.coloresTelas.color',       //  Detalles del color
                    'prendas.coloresTelas.tela',        //  Detalles de la tela
                    'prendas.coloresTelas.fotos',       //  Fotos de cada color-tela
                    'prendas.fotos',                    //  Fotos de referencia de cada prenda
                    'prendas.procesos',                 //  Procesos de producción
                    'prendas.procesos.tipoProceso',     //  Tipo de proceso
                    'prendas.procesos.imagenes',        //  ImÃ¡genes de los procesos
                    'asesor',                           //  Información del asesor
                    'cliente',                          //  Información del cliente
                ])
                ->first();

            if (!$pedido) {
                Log::warning(' [ObtenerPedidoHandler] Pedido no encontrado', [
                    'pedido_id' => $query->getPedidoId(),
                ]);
                return null;
            }

            Log::info(' [ObtenerPedidoHandler] Pedido obtenido', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'prendas_count' => $pedido->prendas?->count() ?? 0,
                'fotos_totales' => $pedido->prendas?->sum(fn($p) => $p->fotos?->count() ?? 0) ?? 0,
            ]);

            return $pedido;

        } catch (\Exception $e) {
            Log::error(' [ObtenerPedidoHandler] Error obteniendo pedido', [
                'pedido_id' => $query->getPedidoId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

