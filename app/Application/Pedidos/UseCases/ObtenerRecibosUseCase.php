<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerRecibosDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;

class ObtenerRecibosUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function ejecutar(ObtenerRecibosDTO $dto): array
    {
        // Obtener modelo Eloquent directamente (no Aggregate) porque necesitamos relaciones
        $pedido = \App\Models\PedidoProduccion::with('epps.imagenes')->findOrFail($dto->pedidoId);

        $recibos = [];
        if ($pedido->epps) {
            foreach ($pedido->epps as $epp) {
                $recibos[] = [
                    'epp_id' => $epp->id,
                    'nombre' => $epp->nombre,
                    'cantidad' => $epp->pivot->cantidad,
                    'observaciones' => $epp->pivot->observaciones,
                    'imagenes' => $epp->imagenes ?? [],
                ];
            }
        }

        return [
            'numero_pedido' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente,
            'fecha' => $pedido->created_at,
            'recibos' => $recibos,
            'total_items' => count($recibos),
        ];
    }
}


