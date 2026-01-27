<?php

namespace App\Domain\Pedidos\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\Pedidos\Queries\ObtenerPrendasPorPedidoQuery;
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

            // ðŸ”„ NO USAR CACHE - Las relaciones (fotos, tallas, etc) pueden cambiar frecuentemente
            // Si necesitas cache, considera invalidarlo cuando se actualiza una prenda
            $prendas = $this->prendaModel
                ->where('pedido_produccion_id', $query->getPedidoId())
                ->with([
                    'variantes',           // manga, broche, bolsillos
                    'tallas',              // tallas por gÃ©nero
                    'coloresTelas',        // combinaciones color-tela
                    'coloresTelas.color',  // detalles del color
                    'coloresTelas.tela',   // detalles de la tela
                    'coloresTelas.fotos',  // fotos de cada color-tela
                    'fotos',               //  AGREGADO: fotos de referencia de la prenda
                    'procesos',            // procesos de producción
                    'procesos.tipoProceso', // tipo de proceso
                    'procesos.imagenes',   // imÃ¡genes de los procesos
                ])
                ->get();

            Log::info(' [ObtenerPrendasPorPedidoHandler] Prendas obtenidas', [
                'pedido_id' => $query->getPedidoId(),
                'cantidad' => $prendas->count(),
                'con_fotos' => $prendas->sum(fn($p) => $p->fotos?->count() ?? 0),
                'con_fotos_telas' => $prendas->sum(fn($p) => 
                    $p->coloresTelas?->sum(fn($ct) => $ct->fotos?->count() ?? 0) ?? 0
                ),
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

