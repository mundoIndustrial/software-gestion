<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\LimpiarEncargadoCosturaCommandDTO;
use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LimpiarEncargadoCosturaUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly ProcesoPrendaRepository $procesos,
    ) {}

    public function execute(LimpiarEncargadoCosturaCommandDTO $cmd): ReciboCommandResultDTO
    {
        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                return new ReciboCommandResultDTO(false, 'No tienes permisos para realizar esta acción', 403);
            }

            $pedido = PedidoProduccion::findOrFail($cmd->pedidoId);

            $recibo = $this->recibos->findActiveByPedidoPrendaTipoAndArea(
                pedidoProduccionId: (int) $pedido->id,
                prendaId: (int) $cmd->prendaId,
                tipoRecibo: (string) $cmd->tipoRecibo,
                area: 'Costura',
            );

            if (!$recibo) {
                return new ReciboCommandResultDTO(false, 'Recibo no encontrado o no está en Costura', 404);
            }

            DB::beginTransaction();

            $procesoCostura = $this->procesos->findLatestByProceso(
                numeroPedido: (int) $pedido->numero_pedido,
                prendaId: (int) $cmd->prendaId,
                proceso: 'Costura',
            );

            if (!$procesoCostura) {
                DB::rollBack();
                return new ReciboCommandResultDTO(false, 'No se encontró proceso de Costura', 404);
            }

            $this->procesos->update($procesoCostura, [
                'encargado' => null,
                'estado_proceso' => 'Pendiente',
            ]);

            DB::commit();

            return new ReciboCommandResultDTO(true, 'Encargado de Costura eliminado correctamente', 200, [
                'proceso_id' => $procesoCostura->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error limpiando encargado de Costura', [
                'pedido_id' => $cmd->pedidoId,
                'prenda_id' => $cmd->prendaId,
                'error' => $e->getMessage(),
            ]);

            return new ReciboCommandResultDTO(false, 'Error al eliminar encargado: ' . $e->getMessage(), 500);
        }
    }
}
