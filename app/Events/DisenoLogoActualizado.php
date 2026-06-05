<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DisenoLogoActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $accion,
        public ?int $disenoId,
        public ?string $estadoAnterior,
        public ?string $estadoNuevo,
        public ?bool $revisada,
        public ?int $pedidoId,
        public ?int $prendaPedidoId,
        public ?int $procesoPrendaDetalleId,
        public ?int $asesorId,
        public ?string $url,
        public int $conteoAsesor,
        public array $conteoNoRevisados,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [new Channel('logos.visualizador')];

        if ($this->asesorId) {
            $channels[] = new Channel('logos.asesor.' . $this->asesorId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'diseno.logo.actualizado';
    }

    public function broadcastWith(): array
    {
        return [
            'accion' => $this->accion,
            'diseno_id' => $this->disenoId,
            'estado_anterior' => $this->estadoAnterior,
            'estado_nuevo' => $this->estadoNuevo,
            'revisada' => $this->revisada,
            'pedido_id' => $this->pedidoId,
            'prenda_pedido_id' => $this->prendaPedidoId,
            'proceso_prenda_detalle_id' => $this->procesoPrendaDetalleId,
            'asesor_id' => $this->asesorId,
            'url' => $this->url,
            'conteo_asesor' => $this->conteoAsesor,
            'conteo_no_revisados' => $this->conteoNoRevisados,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
