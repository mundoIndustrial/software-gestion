<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\CambiarAreaControlCalidadCommandDTO;
use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Domain\Operario\Services\ControlCalidadWorkflow;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Models\PedidoProduccion;
use App\Models\PrendaBodega;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;
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

            if ($cmd->prendaBodegaId !== null) {
                return $this->executeBodega($cmd);
            }

            $pedido = $this->workflowService->findPedidoOrFail($cmd->pedidoId);

            Log::info('[CC] Buscando recibo para cambiar area', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $cmd->prendaId,
                'tipo_recibo' => $cmd->tipoRecibo,
                'numero_recibo' => $cmd->numeroRecibo,
            ]);

            // IMPORTANTE: priorizar el consecutivo exacto recibido desde UI para evitar
            // tomar otro recibo activo de la misma prenda/tipo (ej: 87 vs 106).
            $recibo = $this->recibos->findActiveByPedidoConsecutivoTipo(
                pedidoProduccionId: (int) $pedido->id,
                consecutivoActual: (int) $cmd->numeroRecibo,
                tipoRecibo: (string) $cmd->tipoRecibo,
            );

            if ($recibo && (int) $recibo->prenda_id !== (int) $cmd->prendaId) {
                Log::warning('[CC] Recibo por consecutivo no coincide con prenda solicitada', [
                    'pedido_id' => $pedido->id,
                    'consecutivo' => (int) $cmd->numeroRecibo,
                    'prenda_id_solicitada' => (int) $cmd->prendaId,
                    'prenda_id_recibo' => (int) $recibo->prenda_id,
                    'recibo_id' => (int) $recibo->id,
                ]);
                $recibo = null;
            }

            if (!$recibo) {
                $recibo = $this->recibos->findActiveByPedidoPrendaTipo(
                    pedidoProduccionId: (int) $pedido->id,
                    prendaId: (int) $cmd->prendaId,
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

            $prenda = PrendaPedido::find($cmd->prendaId);

                $recibo->area = 'Control Calidad';
                $this->recibos->save($recibo);

            // Fuente de verdad de completado por área:
            // registrar explícitamente el paso a Control Calidad.
            DB::table('prenda_recibo_completado')->updateOrInsert(
                [
                    'id_recibo' => (int) $recibo->id,
                    'area' => 'Control Calidad',
                ],
                [
                    'numero_recibo' => (int) ($recibo->consecutivo_actual ?? $cmd->numeroRecibo),
                    'nombre_operario' => (string) (auth()->user()->name ?? 'control'),
                    'fecha_completado' => now(),
                    'id_parcial' => null,
                ]
            );

            try {
                broadcast(new \App\Events\ReciboPasadoControlCalidad(
                    $pedido->id,
                    $cmd->prendaId,
                    $recibo->consecutivo_actual,
                    $prenda?->nombre_prenda ?? 'Prenda desconocida',
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
                    'area' => 'Control Calidad',
                    'proceso_actual' => 'Control Calidad',
                    'fecha_creacion' => now()->toISOString(),
                    'numero_pedido' => $pedido->numero_pedido,
                ], 'added', 'pedido'));
            } catch (\Throwable $e) {
                Log::warning('[CC] Error al emitir ControlCalidadUpdated para recibo original', [
                    'pedido_id' => $pedido->id,
                    'prenda_id' => $cmd->prendaId,
                    'numero_recibo' => $recibo->consecutivo_actual,
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

    private function executeBodega(CambiarAreaControlCalidadCommandDTO $cmd): ReciboCommandResultDTO
    {
        $prendaBodega = PrendaBodega::find($cmd->prendaBodegaId);

        Log::info('[CC][BODEGA] Buscando recibo para cambiar area', [
            'prenda_bodega_id' => $cmd->prendaBodegaId,
            'tipo_recibo' => $cmd->tipoRecibo,
            'numero_recibo' => $cmd->numeroRecibo,
        ]);

        $recibo = $this->recibos->findActiveByPedidoPrendaTipo(
            pedidoProduccionId: 0,
            prendaId: 0,
            tipoRecibo: (string) $cmd->tipoRecibo,
            prendaBodegaId: (int) $cmd->prendaBodegaId,
        );

        if (!$recibo) {
            $recibo = $this->recibos->findActiveByPedidoConsecutivoTipo(
                pedidoProduccionId: 0,
                consecutivoActual: (int) $cmd->numeroRecibo,
                tipoRecibo: (string) $cmd->tipoRecibo,
            );
        }

        if (!$recibo) {
            return new ReciboCommandResultDTO(false, 'Recibo no encontrado', 404);
        }

        [$nuevoProceso, $areaPosterior] = $this->workflowService->runInTransaction(function () use ($cmd, $recibo) {
            $areaPosterior = $recibo->area;

            $nuevoProceso = $this->procesos->create([
                'numero_pedido' => null,
                'prenda_pedido_id' => null,
                'prenda_bodega_id' => (int) $cmd->prendaBodegaId,
                'numero_recibo' => $recibo->consecutivo_actual,
                'proceso' => 'Control de Calidad',
                'fecha_inicio' => now(),
                'encargado' => 'control',
                'estado_proceso' => 'En Progreso',
                'codigo_referencia' => 'CC-BOD-' . $recibo->consecutivo_actual . '-' . date('YmdHis'),
            ]);

            $recibo->area = 'Control Calidad';
            $this->recibos->save($recibo);

            DB::table('prenda_recibo_completado')->updateOrInsert(
                [
                    'id_recibo' => (int) $recibo->id,
                    'area' => 'Control Calidad',
                ],
                [
                    'numero_recibo' => (int) ($recibo->consecutivo_actual ?? $cmd->numeroRecibo),
                    'nombre_operario' => (string) (auth()->user()->name ?? 'control'),
                    'fecha_completado' => now(),
                    'id_parcial' => null,
                ]
            );

            return [$nuevoProceso, $areaPosterior];
        });

        $nombrePrenda = $prendaBodega?->nombre ?? 'Prenda de bodega';

        try {
            broadcast(new \App\Events\ReciboPasadoControlCalidad(
                0,
                (int) $cmd->prendaBodegaId,
                $recibo->consecutivo_actual,
                $nombrePrenda,
                $cmd->tipoRecibo
            ));
        } catch (\Throwable $e) {
            Log::warning('[CC][BODEGA] Error al emitir ReciboPasadoControlCalidad', [
                'error' => $e->getMessage(),
            ]);
        }

        return new ReciboCommandResultDTO(true, 'Recibo enviado a Control Calidad correctamente', 200, [
            'proceso_id' => $nuevoProceso->id,
            'proceso_nombre' => 'Control de Calidad',
            'area_anterior' => $areaPosterior,
            'prenda_bodega_id' => (int) $cmd->prendaBodegaId,
        ]);
    }
}
