<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\DeshacerControlCalidadCommandDTO;
use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeshacerControlCalidadUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly ProcesoPrendaRepository $procesos,
        private readonly ControlCalidadWorkflow $workflowService,
    ) {}

    public function execute(DeshacerControlCalidadCommandDTO $cmd): ReciboCommandResultDTO
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
                area: 'Control Calidad',
            );

            if (!$recibo) {
                return new ReciboCommandResultDTO(false, 'Recibo no encontrado o no esta en Control Calidad', 404);
            }

            [$procesoCC, $procesoPosterior, $areaAnterior] = $this->workflowService->runInTransaction(function () use ($pedido, $cmd, $recibo) {
                $procesoCC = $this->procesos->findLatestByProcesoAndNumeroRecibo(
                    numeroPedido: (int) $pedido->numero_pedido,
                    prendaId: (int) $cmd->prendaId,
                    proceso: 'Control de Calidad',
                    numeroRecibo: (int) $recibo->consecutivo_actual,
                );

                if (!$procesoCC) {
                    return [null, null, null];
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

            $prenda = PrendaPedido::find($cmd->prendaId);

            try {
                broadcast(new \App\Events\ControlCalidadUpdated([
                    'id' => (int) $recibo->id,
                    'pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'prenda_id' => (int) $cmd->prendaId,
                    'nombre_prenda' => $prenda?->nombre_prenda,
                    'descripcion' => $prenda?->descripcion,
                    'tipo_recibo' => (string) $cmd->tipoRecibo,
                    'consecutivo_actual' => (string) ($recibo->consecutivo_actual ?? ''),
                    'consecutivo_original' => (string) ($recibo->consecutivo_inicial ?? $recibo->consecutivo_actual ?? ''),
                    'es_parcial' => false,
                    'parcial_id' => null,
                    'completado_area' => false,
                    'area' => $areaAnterior,
                    'proceso_actual' => $areaAnterior,
                    'fecha_creacion' => now()->toISOString(),
                    'numero_pedido' => $pedido->numero_pedido,
                ], 'removed', 'pedido'));
            } catch (\Throwable $e) {
                Log::warning('[CC] Error al emitir ControlCalidadUpdated removido para recibo original', [
                    'pedido_id' => $pedido->id,
                    'prenda_id' => $cmd->prendaId,
                    'numero_recibo' => $recibo->consecutivo_actual,
                    'error' => $e->getMessage(),
                ]);
            }

                return [$procesoCC, $procesoPosterior, $areaAnterior];
            });

            if (!$procesoCC) {
                return new ReciboCommandResultDTO(false, 'No se encontro proceso de Control de Calidad para eliminar', 404);
            }

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
            Log::error('Error deshaciendo Control de Calidad', [
                'pedido_id' => $cmd->pedidoId,
                'prenda_id' => $cmd->prendaId,
                'error' => $e->getMessage(),
            ]);

            return new ReciboCommandResultDTO(false, 'Error al deshacer: ' . $e->getMessage(), 500);
        }
    }
}
