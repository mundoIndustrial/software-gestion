<?php

namespace App\Application\PedidosLogo\Services;

use App\Events\DisenoLogoActualizado;
use App\Models\DisenoLogoPedido;
use Illuminate\Support\Facades\Log;

final class DisenoLogoBroadcastService
{
    public function emit(string $accion, DisenoLogoPedido|array $disenoOrSnapshot, ?string $estadoAnterior = null): void
    {
        try {
            $snapshot = $disenoOrSnapshot instanceof DisenoLogoPedido
                ? $this->snapshotFromModel($disenoOrSnapshot)
                : $disenoOrSnapshot;

            $asesorId = isset($snapshot['asesor_id']) ? (int) $snapshot['asesor_id'] : null;

            broadcast(new DisenoLogoActualizado(
                accion: $accion,
                disenoId: isset($snapshot['id']) ? (int) $snapshot['id'] : null,
                estadoAnterior: $estadoAnterior,
                estadoNuevo: $snapshot['estado'] ?? null,
                revisada: array_key_exists('revisada', $snapshot) ? (bool) $snapshot['revisada'] : null,
                pedidoId: isset($snapshot['pedido_id']) ? (int) $snapshot['pedido_id'] : null,
                prendaPedidoId: isset($snapshot['prenda_pedido_id']) ? (int) $snapshot['prenda_pedido_id'] : null,
                procesoPrendaDetalleId: isset($snapshot['proceso_prenda_detalle_id']) ? (int) $snapshot['proceso_prenda_detalle_id'] : null,
                asesorId: $asesorId,
                url: $snapshot['url'] ?? null,
                conteoAsesor: $asesorId ? $this->contarPendientesAsesor($asesorId) : 0,
                conteoNoRevisados: $this->contarNoRevisadosVisualizador(),
            ));
        } catch (\Throwable $e) {
            Log::warning('[DisenoLogoBroadcastService] No se pudo emitir broadcast', [
                'accion' => $accion,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function snapshotFromModel(DisenoLogoPedido $diseno): array
    {
        $diseno->loadMissing(['proceso.prenda.pedidoProduccion']);

        $pedido = $diseno->proceso?->prenda?->pedidoProduccion;

        return [
            'id' => $diseno->id,
            'url' => $diseno->url,
            'estado' => $diseno->estado,
            'revisada' => (bool) $diseno->revisada,
            'proceso_prenda_detalle_id' => $diseno->proceso_prenda_detalle_id,
            'prenda_pedido_id' => $diseno->proceso?->prenda_pedido_id,
            'pedido_id' => $pedido?->id,
            'asesor_id' => $pedido?->asesor_id,
        ];
    }

    private function contarPendientesAsesor(int $asesorId): int
    {
        return (int) DisenoLogoPedido::query()
            ->where('estado', 'pendiente_por_confirmar')
            ->whereHas('proceso.prenda.pedidoProduccion', function ($query) use ($asesorId) {
                $query->where('asesor_id', $asesorId);
            })
            ->count();
    }

    private function contarNoRevisadosVisualizador(): array
    {
        $confirmados = (int) DisenoLogoPedido::query()
            ->where('estado', 'logo_confirmado')
            ->where('revisada', 0)
            ->count();

        $devueltos = (int) DisenoLogoPedido::query()
            ->where('estado', 'devuelto_a_diseño')
            ->where('revisada', 0)
            ->count();

        return [
            'confirmados' => $confirmados,
            'devueltos' => $devueltos,
            'total' => $confirmados + $devueltos,
        ];
    }
}
