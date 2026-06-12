<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ControlCalidadUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orden;
    public $action; // 'added', 'removed'
    public $tipo; // 'pedido', 'bodega'

    /**
     * Create a new event instance.
     */
    public function __construct($orden, $action, $tipo)
    {
        $this->orden = $orden;
        $this->action = $action;
        $this->tipo = $tipo;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('control-calidad');
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        $orden = $this->orden;
        $destinoCostura = $this->resolverDestinoCostura();

        if (is_array($orden)) {
            $orden['destino_costura'] = $destinoCostura;
        } elseif (is_object($orden)) {
            $orden->destino_costura = $destinoCostura;
        }

        return [
            'orden' => $orden,
            'action' => $this->action,
            'tipo' => $this->tipo,
        ];
    }

    private function resolverDestinoCostura(): ?string
    {
        $destinoExistente = trim((string) (
            data_get($this->orden, 'destino_costura', '')
        ));
        if ($destinoExistente !== '') {
            return $destinoExistente;
        }

        $idsRecibos = collect([
            data_get($this->orden, 'recibo_id'),
            data_get($this->orden, 'id'),
            data_get($this->orden, 'recibos.0.id'),
        ])->filter(fn ($valor) => (int) $valor > 0)->map(fn ($valor) => (int) $valor)->values()->all();

        $numeroRecibos = collect([
            data_get($this->orden, 'numero_recibo'),
            data_get($this->orden, 'consecutivo_actual'),
            data_get($this->orden, 'recibos.0.consecutivo_actual'),
        ])->filter(fn ($valor) => (int) preg_replace('/[^0-9]/', '', (string) $valor) > 0)
            ->map(fn ($valor) => (int) preg_replace('/[^0-9]/', '', (string) $valor))
            ->values()
            ->all();

        $idsParciales = collect([
            data_get($this->orden, 'parcial_id'),
            data_get($this->orden, 'pedido_parcial_id'),
            data_get($this->orden, 'recibos.0.parcial_id'),
        ])->filter(fn ($valor) => (int) $valor > 0)->map(fn ($valor) => (int) $valor)->values()->all();

        $queryBase = DB::table('prenda_recibo_completado')
            ->whereRaw('LOWER(TRIM(COALESCE(area, ""))) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->whereNotNull('destino_costura')
            ->whereRaw('TRIM(COALESCE(destino_costura, "")) <> ""');

        if (!empty($idsRecibos)) {
            $destino = (clone $queryBase)
                ->whereIn('id_recibo', $idsRecibos)
                ->orderByDesc('fecha_completado')
                ->value('destino_costura');

            if (is_string($destino) && trim($destino) !== '') {
                return trim($destino);
            }
        }

        if (!empty($numeroRecibos)) {
            $destino = (clone $queryBase)
                ->whereIn('numero_recibo', $numeroRecibos)
                ->orderByDesc('fecha_completado')
                ->value('destino_costura');

            if (is_string($destino) && trim($destino) !== '') {
                return trim($destino);
            }
        }

        if (!empty($idsParciales)) {
            $destino = (clone $queryBase)
                ->whereIn('id_parcial', $idsParciales)
                ->orderByDesc('fecha_completado')
                ->value('destino_costura');

            if (is_string($destino) && trim($destino) !== '') {
                return trim($destino);
            }
        }

        return null;
    }
}
