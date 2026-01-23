<?php

namespace App\Domain\Pedidos\QueryHandlers;

use App\Domain\Shared\CQRS\Query;
use App\Domain\Shared\CQRS\QueryHandler;
use App\Domain\Pedidos\Queries\BuscarPedidoPorNumeroQuery;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * BuscarPedidoPorNumeroHandler
 * 
 * Maneja BuscarPedidoPorNumeroQuery
 * Busca un pedido por nÃºmero Ãºnico
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
            Log::info('ðŸ”Ž [BuscarPedidoPorNumeroHandler] Buscando pedido', [
                'numero_pedido' => $query->getNumeroPedido(),
            ]);

            // ðŸ”„ NO USAR CACHE - Las relaciones pueden cambiar frecuentemente
            $pedido = $this->pedidoModel
                ->where('numero_pedido', $query->getNumeroPedido())
                ->with([
                    'prendas',                          // âœ… Prendas del pedido
                    'prendas.variantes',                // âœ… Variantes (manga, broche)
                    'prendas.tallas',                   // âœ… Tallas
                    'prendas.coloresTelas',             // âœ… Colores-telas
                    'prendas.coloresTelas.color',       // âœ… Detalles del color
                    'prendas.coloresTelas.tela',        // âœ… Detalles de la tela
                    'prendas.coloresTelas.fotos',       // âœ… Fotos de color-tela
                    'prendas.fotos',                    // âœ… Fotos de prenda
                    'prendas.procesos',                 // âœ… Procesos
                    'prendas.procesos.tipoProceso',     // âœ… Tipo de proceso
                    'prendas.procesos.imagenes',        // âœ… ImÃ¡genes de procesos
                    'asesor',                           // âœ… Asesor
                    'cliente',                          // âœ… Cliente
                ])
                ->first();

            if (!$pedido) {
                Log::warning(' [BuscarPedidoPorNumeroHandler] Pedido no encontrado', [
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

