<?php

namespace App\Application\Pedidos\Despacho\DTOs;

use Carbon\Carbon;

/**
 * ControlEntregasDTO
 * 
 * Data Transfer Object para el control de entregas completo
 */
class ControlEntregasDTO
{
    public function __construct(
        public int|string $pedidoId,
        public string $numeroPedido,
        public string $cliente,
        public ?Carbon $fechaHora = null,
        public ?string $clienteEmpresa = null,
        /** @var DespachoParcialesDTO[] */
        public array $despachos = [],
    ) {
        if ($this->fechaHora === null) {
            $this->fechaHora = now();
        }
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'pedido_id' => $this->pedidoId,
            'numero_pedido' => $this->numeroPedido,
            'cliente' => $this->cliente,
            'fecha_hora' => $this->fechaHora,
            'cliente_empresa' => $this->clienteEmpresa,
            'despachos' => array_map(fn($d) => $d->toArray(), $this->despachos),
        ];
    }
}
