<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\DeshacerCosturaCommandDTO;
use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Domain\Operario\Services\ControlCalidadWorkflow;
use App\Models\PrendaBodega;
use Illuminate\Support\Facades\Log;

class DeshacerCosturaUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly ProcesoPrendaRepository $procesos,
        private readonly ControlCalidadWorkflow $workflowService,
    ) {}

    public function execute(DeshacerCosturaCommandDTO $cmd): ReciboCommandResultDTO
    {
        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                return new ReciboCommandResultDTO(false, 'No tienes permisos para realizar esta accion', 403);
            }

            if ($cmd->prendaBodegaId !== null) {
                return $this->executeBodega($cmd);
            }

            $pedido = $this->workflowService->findPedidoOrFail($cmd->pedidoId);
            $prenda = $this->workflowService->findPrendaById($cmd->prendaId);

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

            Log::info('[DESHACER-COSTURA] Resultado busqueda recibo', [
                'recibo_encontrado' => $recibo ? true : false,
                'recibo_id' => $recibo?->id,
                'recibo_numero' => $recibo?->consecutivo_actual,
                'recibo_area' => $recibo?->area,
            ]);

            if (!$recibo) {
                return new ReciboCommandResultDTO(false, 'Recibo no encontrado o no esta en Costura', 404);
            }

            $procesoCostura = $this->workflowService->runInTransaction(function () use ($pedido, $cmd, $recibo) {
                $procesoCostura = $this->procesos->findLatestByProcesoAndNumeroRecibo(
                    numeroPedido: (int) $pedido->numero_pedido,
                    prendaId: (int) $cmd->prendaId,
                    proceso: 'Costura',
                    numeroRecibo: (int) $recibo->consecutivo_actual,
                );

                if (!$procesoCostura) {
                    return null;
                }

                $this->procesos->update($procesoCostura, [
                    'encargado' => null,
                    'fecha_de_asignacion_encargado' => null,
                    'estado_proceso' => 'Pendiente',
                ]);

                return $procesoCostura;
            });

            if (!$procesoCostura) {
                return new ReciboCommandResultDTO(false, 'No se encontro proceso de Costura para limpiar encargado', 404);
            }

            Log::info('Encargado de Costura limpiado', [
                'pedido_id' => $cmd->pedidoId,
                'prenda_id' => $cmd->prendaId,
                'proceso_id' => $procesoCostura->id,
                'area_mantenida' => 'Costura',
                'encargado_eliminado' => true,
                'fecha_asignacion_eliminada' => true,
                'usuario_id' => auth()->id(),
            ]);

            return new ReciboCommandResultDTO(true, 'Encargado de Costura eliminado correctamente', 200, [
                'area_nueva' => 'Costura',
                'proceso_anterior' => 'Costura',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deshaciendo Costura', [
                'pedido_id' => $cmd->pedidoId,
                'prenda_id' => $cmd->prendaId,
                'error' => $e->getMessage(),
            ]);

            return new ReciboCommandResultDTO(false, 'Error al deshacer: ' . $e->getMessage(), 500);
        }
    }

    private function executeBodega(DeshacerCosturaCommandDTO $cmd): ReciboCommandResultDTO
    {
        $prendaBodega = PrendaBodega::find($cmd->prendaBodegaId);

        $recibo = $this->recibos->findActiveByPedidoPrendaTipo(
            pedidoProduccionId: 0,
            prendaId: 0,
            tipoRecibo: (string) $cmd->tipoRecibo,
            prendaBodegaId: (int) $cmd->prendaBodegaId,
        );

        if (!$recibo) {
            return new ReciboCommandResultDTO(false, 'Recibo no encontrado o no esta en Costura', 404);
        }

        $procesoCostura = $this->workflowService->runInTransaction(function () use ($cmd, $recibo) {
            $procesosCostura = \App\Models\ProcesoPrenda::query()
                ->where('prenda_bodega_id', (int) $cmd->prendaBodegaId)
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                ->whereNull('deleted_at')
                ->get();

            if ($procesosCostura->isEmpty()) {
                return null;
            }

            foreach ($procesosCostura as $proceso) {
                $this->procesos->update($proceso, [
                    'encargado' => null,
                    'fecha_de_asignacion_encargado' => null,
                    'estado_proceso' => 'Pendiente',
                ]);
            }

            return $procesosCostura->sortByDesc('created_at')->first();
        });

        if (!$procesoCostura) {
            return new ReciboCommandResultDTO(false, 'No se encontro proceso de Costura para limpiar encargado', 404);
        }

        return new ReciboCommandResultDTO(true, 'Encargado de Costura eliminado correctamente', 200, [
            'area_nueva' => 'Costura',
            'proceso_anterior' => 'Costura',
            'prenda_bodega_id' => (int) $cmd->prendaBodegaId,
            'nombre_prenda' => $prendaBodega?->nombre ?? null,
        ]);
    }
}
