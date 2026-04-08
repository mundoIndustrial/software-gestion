<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\LimpiarEncargadoCosturaCommandDTO;
use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Domain\Operario\Services\ControlCalidadWorkflow;
use Illuminate\Support\Facades\Log;

class LimpiarEncargadoCosturaUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly ProcesoPrendaRepository $procesos,
        private readonly ControlCalidadWorkflow $workflowService,
    ) {}

    public function execute(LimpiarEncargadoCosturaCommandDTO $cmd): ReciboCommandResultDTO
    {
        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                return new ReciboCommandResultDTO(false, 'No tienes permisos para realizar esta accion', 403);
            }

            $pedido = $this->workflowService->findPedidoOrFail($cmd->pedidoId);

            $recibo = $this->recibos->findActiveByPedidoPrendaTipoAndArea(
                pedidoProduccionId: (int) $pedido->id,
                prendaId: (int) $cmd->prendaId,
                tipoRecibo: (string) $cmd->tipoRecibo,
                area: 'Costura',
            );

            if (!$recibo) {
                return new ReciboCommandResultDTO(false, 'Recibo no encontrado o no esta en Costura', 404);
            }

            $procesoCostura = $this->workflowService->runInTransaction(function () use ($pedido, $cmd) {
                $procesoCostura = $this->procesos->findLatestByProceso(
                    numeroPedido: (int) $pedido->numero_pedido,
                    prendaId: (int) $cmd->prendaId,
                    proceso: 'Costura',
                );

                if (!$procesoCostura) {
                    return null;
                }

                $this->procesos->update($procesoCostura, [
                    'encargado' => null,
                    'estado_proceso' => 'Pendiente',
                ]);

                return $procesoCostura;
            });

            if (!$procesoCostura) {
                return new ReciboCommandResultDTO(false, 'No se encontro proceso de Costura', 404);
            }

            return new ReciboCommandResultDTO(true, 'Encargado de Costura eliminado correctamente', 200, [
                'proceso_id' => $procesoCostura->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error limpiando encargado de Costura', [
                'pedido_id' => $cmd->pedidoId,
                'prenda_id' => $cmd->prendaId,
                'error' => $e->getMessage(),
            ]);

            return new ReciboCommandResultDTO(false, 'Error al eliminar encargado: ' . $e->getMessage(), 500);
        }
    }
}
