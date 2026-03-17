<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\DeshacerControlCalidadCommandDTO;
use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeshacerControlCalidadUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly ProcesoPrendaRepository $procesos,
    ) {}

    public function execute(DeshacerControlCalidadCommandDTO $cmd): ReciboCommandResultDTO
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
                area: 'Control Calidad',
            );

            if (!$recibo) {
                return new ReciboCommandResultDTO(false, 'Recibo no encontrado o no está en Control Calidad', 404);
            }

            DB::beginTransaction();

            $procesoCC = $this->procesos->findLatestByProcesoAndNumeroRecibo(
                numeroPedido: (int) $pedido->numero_pedido,
                prendaId: (int) $cmd->prendaId,
                proceso: 'Control de Calidad',
                numeroRecibo: (int) $recibo->consecutivo_actual,
            );

            if (!$procesoCC) {
                DB::rollBack();
                return new ReciboCommandResultDTO(false, 'No se encontró proceso de Control de Calidad para eliminar', 404);
            }

            $procesoPosterior = $this->procesos->findLatestNotProcesoByNumeroRecibo(
                numeroPedido: (int) $pedido->numero_pedido,
                prendaId: (int) $cmd->prendaId,
                procesoExcluido: 'Control de Calidad',
                numeroRecibo: (int) $recibo->consecutivo_actual,
            );

            $areaAnterior = $procesoPosterior ? $procesoPosterior->proceso : 'Costura';

            $recibo->update([
                'area' => $areaAnterior,
            ]);

            $this->procesos->forceDelete($procesoCC);

            DB::commit();

            Log::info('Proceso de Control de Calidad deshecho', [
                'pedido_id' => $cmd->pedidoId,
                'prenda_id' => $cmd->prendaId,
                'proceso_id' => $procesoCC->id,
                'area_anterior' => $areaAnterior,
                'usuario_id' => auth()->id(),
            ]);

            return new ReciboCommandResultDTO(true, 'Control de Calidad deshecho correctamente', 200, [
                'area_nueva' => $areaAnterior,
                'proceso_anterior' => $procesoPosterior ? $procesoPosterior->proceso : null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deshaciendo Control de Calidad', [
                'pedido_id' => $cmd->pedidoId,
                'prenda_id' => $cmd->prendaId,
                'error' => $e->getMessage(),
            ]);

            return new ReciboCommandResultDTO(false, 'Error al deshacer: ' . $e->getMessage(), 500);
        }
    }
}
