<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\CambiarAreaControlCalidadCommandDTO;
use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Domain\Operario\Services\ControlCalidadWorkflow;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use Illuminate\Support\Facades\Log;

class CambiarAreaControlCalidadUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly ProcesoPrendaRepository $procesos,
        private readonly ControlCalidadWorkflow $workflowService,
    ) {}

    public function execute(CambiarAreaControlCalidadCommandDTO $cmd): ReciboCommandResultDTO
    {
        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                throw new \InvalidArgumentException('No tienes permisos para realizar esta accion');
            }

            $pedido = $this->workflowService->findPedidoOrFail($cmd->pedidoId);

            Log::info('[CC] Buscando recibo para cambiar area', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $cmd->prendaId,
                'tipo_recibo' => $cmd->tipoRecibo,
                'numero_recibo' => $cmd->numeroRecibo,
            ]);

            $recibo = $this->recibos->findActiveByPedidoPrendaTipo(
                pedidoProduccionId: (int) $pedido->id,
                prendaId: (int) $cmd->prendaId,
                tipoRecibo: (string) $cmd->tipoRecibo,
            );

            if (!$recibo) {
                $recibo = $this->recibos->findActiveByPedidoConsecutivoTipo(
                    pedidoProduccionId: (int) $pedido->id,
                    consecutivoActual: (int) $cmd->numeroRecibo,
                    tipoRecibo: (string) $cmd->tipoRecibo,
                );
            }

            if (!$recibo) {
                Log::error('[CC] Recibo no encontrado - diagnostico', [
                    'pedido_id' => $pedido->id,
                    'prenda_id_buscado' => $cmd->prendaId,
                    'tipo_buscado' => $cmd->tipoRecibo,
                ]);

                throw new \InvalidArgumentException('Recibo no encontrado');
            }

            [$nuevoProceso, $areaPosterior] = $this->workflowService->runInTransaction(function () use ($pedido, $cmd, $recibo) {
                $areaPosterior = $recibo->area;

                $nuevoProceso = $this->procesos->create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $cmd->prendaId,
                    'numero_recibo' => $recibo->consecutivo_actual,
                    'proceso' => 'Control de Calidad',
                    'fecha_inicio' => now(),
                    'encargado' => 'control',
                    'estado_proceso' => 'En Progreso',
                    'codigo_referencia' => 'CC-' . $recibo->consecutivo_actual . '-' . date('YmdHis'),
                ]);

                $recibo->area = 'Control Calidad';
                $this->recibos->save($recibo);

                try {
                    broadcast(new \App\Events\ReciboPasadoControlCalidad(
                        $pedido->id,
                        $cmd->prendaId,
                        $recibo->consecutivo_actual,
                        $this->workflowService->resolvePrendaNombre($cmd->prendaId),
                        $cmd->tipoRecibo
                    ));

                    Log::info('Broadcast enviado a costureros - recibo pasado a Control Calidad', [
                        'pedido_id' => $pedido->id,
                        'prenda_id' => $cmd->prendaId,
                        'numero_recibo' => $recibo->consecutivo_actual,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Error al enviar broadcast a costureros', [
                        'error' => $e->getMessage(),
                    ]);
                }

                return [$nuevoProceso, $areaPosterior];
            });

            Log::info('Recibo enviado a Control Calidad', [
                'pedido_id' => $cmd->pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $cmd->prendaId,
                'numero_recibo' => $recibo->consecutivo_actual,
                'proceso_id' => $nuevoProceso->id,
                'usuario_id' => auth()->id(),
            ]);

            return new ReciboCommandResultDTO(true, 'Recibo enviado a Control Calidad correctamente', 200, [
                'proceso_id' => $nuevoProceso->id,
                'proceso_nombre' => 'Control de Calidad',
                'area_anterior' => $areaPosterior,
            ]);

        } catch (\InvalidArgumentException $e) {
            return new ReciboCommandResultDTO(false, $e->getMessage(), 400);

        } catch (\Exception $e) {
            Log::error('Error cambiando area de recibo a Control Calidad', [
                'pedido_id' => $cmd->pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new ReciboCommandResultDTO(false, 'Error al cambiar el area: ' . $e->getMessage(), 500);
        }
    }
}
