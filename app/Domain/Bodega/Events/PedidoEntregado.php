<?php

namespace App\Domain\Bodega\Events;

use App\Domain\Bodega\ValueObjects\EstadoPedido;
use Carbon\Carbon;

/**
 * Domain Event: Pedido entregado
 * Se dispara cuando un pedido es marcado como entregado
 */
class PedidoEntregado
{
    private int $pedidoId;
    private string $numeroPedido;
    private EstadoPedido $estadoAnterior;
    private EstadoPedido $estadoNuevo;
    private Carbon $fechaEntrega;
    private Carbon $ocurridoEn;

    public function __construct(
        int $pedidoId,
        string $numeroPedido,
        EstadoPedido $estadoAnterior,
        EstadoPedido $estadoNuevo,
        Carbon $fechaEntrega
    ) {
        $this->pedidoId = $pedidoId;
        $this->numeroPedido = $numeroPedido;
        $this->estadoAnterior = $estadoAnterior;
        $this->estadoNuevo = $estadoNuevo;
        $this->fechaEntrega = $fechaEntrega;
        $this->ocurridoEn = Carbon::now();
    }

    public function getPedidoId(): int
    {
        return $this->pedidoId;
    }

    public function getNumeroPedido(): string
    {
        return $this->numeroPedido;
    }

    public function getEstadoAnterior(): EstadoPedido
    {
        return $this->estadoAnterior;
    }

    public function getEstadoNuevo(): EstadoPedido
    {
        return $this->estadoNuevo;
    }

    public function getFechaEntrega(): Carbon
    {
        return $this->fechaEntrega;
    }

    public function getOcurridoEn(): Carbon
    {
        return $this->ocurridoEn;
    }

    /**
     * Obtener datos del evento para serialización
     */
    public function toArray(): array
    {
        return [
            'pedido_id' => $this->pedidoId,
            'numero_pedido' => $this->numeroPedido,
            'estado_anterior' => $this->estadoAnterior->getValor(),
            'estado_nuevo' => $this->estadoNuevo->getValor(),
            'fecha_entrega' => $this->fechaEntrega->toDateTimeString(),
            'ocurrido_en' => $this->ocurridoEn->toDateTimeString(),
            'tipo_evento' => 'pedido_entregado'
        ];
    }

    public function __toString(): string
    {
        return "Pedido {$this->numeroPedido} entregado el {$this->fechaEntrega->format('d/m/Y H:i')}";
    }
}
