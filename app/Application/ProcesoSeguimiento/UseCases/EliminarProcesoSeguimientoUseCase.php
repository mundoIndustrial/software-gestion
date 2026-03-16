<?php

namespace App\Application\ProcesoSeguimiento\UseCases;

use App\Domain\ProcesoSeguimiento\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\ProcesoSeguimiento\Repositories\ProcesoPrendaSeguimientoRepository;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: EliminarProcesoSeguimientoUseCase
 *
 * Elimina definitivamente un proceso (forceDelete) y sincroniza el consecutivo
 * de recibos: lo apunta al proceso más reciente restante, o lo revierte a
 * "Insumos / Pendiente_Insumos" si ya no quedan procesos para esa prenda.
 */
final class EliminarProcesoSeguimientoUseCase
{
    public function __construct(
        private readonly ProcesoPrendaSeguimientoRepository $procesosRepo,
        private readonly ConsecutivoReciboPedidoRepository  $consecutivosRepo,
    ) {}

    public function execute(int $procesoId): void
    {
        $proceso      = ProcesoPrenda::findOrFail($procesoId);
        $prendaId     = (int) $proceso->prenda_pedido_id;
        $numeroPedido = (int) $proceso->numero_pedido;

        $this->procesosRepo->eliminar($procesoId);

        Log::info('[EliminarProcesoSeguimientoUseCase] Proceso eliminado', [
            'proceso_id'   => $procesoId,
            'prenda_id'    => $prendaId,
            'numero_pedido' => $numeroPedido,
        ]);

        $prenda = PrendaPedido::find($prendaId);

        if (!$prenda || !$prenda->pedido_produccion_id) {
            return;
        }

        $this->sincronizarConsecutivo($prenda, $prendaId, $numeroPedido);
    }

    private function sincronizarConsecutivo(PrendaPedido $prenda, int $prendaId, int $numeroPedido): void
    {
        try {
            $consecutivo = $this->consecutivosRepo->encontrarPorPedidoYPrenda(
                (int) $prenda->pedido_produccion_id, $prendaId
            );

            if (!$consecutivo) {
                return;
            }

            $procesoMasReciente = $this->procesosRepo->encontrarMasReciente($prendaId, $numeroPedido);

            if ($procesoMasReciente) {
                $this->consecutivosRepo->actualizarArea($consecutivo, $procesoMasReciente->proceso);
            } else {
                $this->consecutivosRepo->actualizarArea($consecutivo, 'Insumos', 'Pendiente_Insumos');
            }
        } catch (\Exception $e) {
            Log::warning('[EliminarProcesoSeguimientoUseCase] Error sincronizando consecutivo: ' . $e->getMessage());
        }
    }
}
