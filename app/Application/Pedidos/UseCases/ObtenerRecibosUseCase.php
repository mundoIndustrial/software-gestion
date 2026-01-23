<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerRecibosDTO;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;

class ObtenerRecibosUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository
    ) {}

    public function ejecutar(ObtenerRecibosDTO $dto): array
    {
        // Obtener el pedido
        $pedido = $this->pedidoRepository->obtenerPorId($dto->pedidoId);

        if (!$pedido) {
            throw new \Exception("Pedido con ID {$dto->pedidoId} no encontrado");
        }

        // Obtener recibos del EPP si existen
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
