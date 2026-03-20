<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ListOrdersResponse
{
    private $ordenes;
    private array $estados;
    private array $pedidosSeleccionados;

    public function __construct($ordenes, array $estados, array $pedidosSeleccionados)
    {
        $this->ordenes = $ordenes;
        $this->estados = $estados;
        $this->pedidosSeleccionados = $pedidosSeleccionados;
    }

    public function getOrdenes() { return $this->ordenes; }
    public function getEstados(): array { return $this->estados; }
    public function getPedidosSeleccionados(): array { return $this->pedidosSeleccionados; }

    public function toArray(): array
    {
        return [
            'ordenes' => $this->ordenes,
            'estados' => $this->estados,
            'pedidosSeleccionados' => $this->pedidosSeleccionados,
        ];
    }
}
