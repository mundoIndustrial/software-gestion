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
                    'prendas',                          //  Prendas del pedido
                    'prendas.variantes',                //  Variantes (manga, broche)
                    'prendas.tallas',                   //  Tallas
                    'prendas.coloresTelas',             //  Colores-telas
                    'prendas.coloresTelas.color',       //  Detalles del color
                    'prendas.coloresTelas.tela',        //  Detalles de la tela
                    'prendas.coloresTelas.fotos',       //  Fotos de color-tela
                    'prendas.fotos',                    //  Fotos de prenda
                    'prendas.procesos',                 //  Procesos
                    'prendas.procesos.tipoProceso',     //  Tipo de proceso
                    'prendas.procesos.imagenes',        //  ImÃ¡genes de procesos
                    'asesor',                           //  Asesor
                    'cliente',                          //  Cliente
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

