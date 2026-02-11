<?php

namespace App\Domain\Bodega\Events;

use App\Domain\Bodega\ValueObjects\EstadoPedido;
use Carbon\Carbon;

/**
 * Domain Event: Pedido actualizado
 * Se dispara cuando el estado de un pedido cambia
 */
class PedidoActualizado
{
    private int $pedidoId;
    private string $numeroPedido;
    private EstadoPedido $estadoAnterior;
    private EstadoPedido $estadoNuevo;
    private Carbon $ocurridoEn;

    public function __construct(
        int $pedidoId,
        string $numeroPedido,
        EstadoPedido $estadoAnterior,
        EstadoPedido $estadoNuevo
    ) {
        $this->pedidoId = $pedidoId;
        $this->numeroPedido = $numeroPedido;
        $this->estadoAnterior = $estadoAnterior;
        $this->estadoNuevo = $estadoNuevo;
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

    public function getOcurridoEn(): Carbon
    {
        return $this->ocurridoEn;
    }

    /**
     * Verificar si es una transición importante
     */
    public function esTransicionImportante(): bool
    {
        $transicionesImportantes = [
            'NO INICIADO' => 'EN EJECUCIÓN',
            'EN EJECUCIÓN' => 'ENTREGADO',
            'PENDIENTE_INSUMOS' => 'NO INICIADO',
            'PENDIENTE_SUPERVISOR' => 'NO INICIADO'
        ];

        $clave = $this->estadoAnterior->getValor() . ' -> ' . $this->estadoNuevo->getValor();
        return in_array($clave, $transicionesImportantes);
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
            'ocurrido_en' => $this->ocurridoEn->toDateTimeString(),
            'tipo_evento' => 'pedido_actualizado',
            'es_importante' => $this->esTransicionImportante()
        ];
    }

    public function __toString(): string
    {
        return "Pedido {$this->numeroPedido} actualizado de {$this->estadoAnterior->getValor()} a {$this->estadoNuevo->getValor()}";
    }
}
