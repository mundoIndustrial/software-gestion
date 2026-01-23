<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;

class ObtenerFacturaUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoProduccionRepository $pedidoRepository
    ) {}

    public function ejecutar(ObtenerFacturaDTO $dto): array
    {
        $pedido = $this->validarPedidoExiste($dto->pedidoId, $this->pedidoRepository);

        return [
            'numero_pedido' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente,
            'fecha' => $pedido->created_at,
            'forma_de_pago' => $pedido->forma_de_pago,
            'estado' => $pedido->estado,
            'total' => $this->calcularTotal($pedido),
            'prendas' => $pedido->prendas,
        ];
    }

    private function calcularTotal($pedido): float
    {
        $total = 0;
        foreach ($pedido->prendas as $prenda) {
            $total += $prenda->precio_unitario * $prenda->cantidad;
        }
        return $total;
    }
}
