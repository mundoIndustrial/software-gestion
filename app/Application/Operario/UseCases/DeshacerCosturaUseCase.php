<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\DeshacerCosturaCommandDTO;
use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Models\PedidoProduccion;
use App\Models\Prenda;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeshacerCosturaUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly ProcesoPrendaRepository $procesos,
    ) {}

    public function execute(DeshacerCosturaCommandDTO $cmd): ReciboCommandResultDTO
    {
        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                return new ReciboCommandResultDTO(false, 'No tienes permisos para realizar esta acción', 403);
            }

            $pedido = PedidoProduccion::findOrFail($cmd->pedidoId);

            $prenda = Prenda::find($cmd->prendaId);

            Log::info('[DESHACER-COSTURA] Buscando recibo', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $cmd->prendaId,
                'tipo_recibo' => $cmd->tipoRecibo,
                'prenda_encontrada' => $prenda ? true : false,
            ]);

            $recibo = $this->recibos->findActiveByPedidoPrendaTipo(
                pedidoProduccionId: (int) $pedido->id,
                prendaId: (int) $cmd->prendaId,
                tipoRecibo: (string) $cmd->tipoRecibo,
            );

            Log::info('[DESHACER-COSTURA] Resultado búsqueda recibo', [
                'recibo_encontrado' => $recibo ? true : false,
                'recibo_id' => $recibo?->id,
                'recibo_numero' => $recibo?->consecutivo_actual,
                'recibo_area' => $recibo?->area,
            ]);

            if (!$recibo) {
                return new ReciboCommandResultDTO(false, 'Recibo no encontrado o no está en Costura', 404);
            }

            DB::beginTransaction();

            $procesoCostura = $this->procesos->findLatestByProcesoAndNumeroRecibo(
                numeroPedido: (int) $pedido->numero_pedido,
                prendaId: (int) $cmd->prendaId,
                proceso: 'Costura',
                numeroRecibo: (int) $recibo->consecutivo_actual,
            );

            if (!$procesoCostura) {
                DB::rollBack();
                return new ReciboCommandResultDTO(false, 'No se encontró proceso de Costura para limpiar encargado', 404);
            }

            $this->procesos->update($procesoCostura, [
                'encargado' => null,
                'estado_proceso' => 'Pendiente',
            ]);

            DB::commit();

            Log::info('Encargado de Costura limpiado', [
                'pedido_id' => $cmd->pedidoId,
                'prenda_id' => $cmd->prendaId,
                'proceso_id' => $procesoCostura->id,
                'area_mantenida' => 'Costura',
                'usuario_id' => auth()->id(),
            ]);

            return new ReciboCommandResultDTO(true, 'Encargado de Costura eliminado correctamente', 200, [
                'area_nueva' => 'Costura',
                'proceso_anterior' => 'Costura',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deshaciendo Costura', [
                'pedido_id' => $cmd->pedidoId,
                'prenda_id' => $cmd->prendaId,
                'error' => $e->getMessage(),
            ]);

            return new ReciboCommandResultDTO(false, 'Error al deshacer: ' . $e->getMessage(), 500);
        }
    }
}
