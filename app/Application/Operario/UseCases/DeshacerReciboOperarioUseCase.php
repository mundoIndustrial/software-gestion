<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Events\OperarioRecibosActualizados;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Domain\Operario\Services\ReciboOperarioWorkflow;
use App\Models\ReciboPorPartes;
use Illuminate\Support\Facades\Auth;

class DeshacerReciboOperarioUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly ProcesoPrendaRepository $procesos,
        private readonly ReciboOperarioWorkflow $workflow,
    ) {}

    public function execute(int $idRecibo, bool $esParcial = false): ReciboCommandResultDTO
    {
        try {
            $usuario = Auth::user();

            $esCortador = $usuario->hasRole('cortador') || $usuario->hasRole('visualizador_plooter');
            $esCosturero = $usuario->hasRole('costurero');
            $esConfeccionSobremedida = $usuario->hasRole('confeccion-sobremedida');
            $esCosturaReflectivo = $usuario->hasRole('costura-reflectivo');
            $esLiderReflectivo = $usuario->hasRole('lider-reflectivo');
            $esAdminCostura = $usuario->hasRole('administrador-costura');
            $areaOperario = $esCortador
                ? 'Corte'
                : (($esCosturero || $esConfeccionSobremedida || $esCosturaReflectivo || $esLiderReflectivo || $esAdminCostura) ? 'Costura' : null);
            if (!$areaOperario) {
                return new ReciboCommandResultDTO(false, 'Rol no autorizado', 403);
            }

            if ($esParcial && $areaOperario !== 'Costura') {
                return new ReciboCommandResultDTO(false, 'Solo los roles de Costura pueden deshacer parciales', 403);
            }

            if ($esParcial) {
                $parcial = $this->workflow->findParcialById((int) $idRecibo, false);

                if (!$parcial) {
                    return new ReciboCommandResultDTO(false, 'Parcial no encontrado', 404);
                }

                $this->workflow->deleteCompletadoByParcialAndArea((int) $parcial->id, $areaOperario);

                $estadoOriginal = $this->sincronizarCompletadoOriginalDesdeParciales($parcial, $areaOperario);
                $this->notificarVistaCosturaDeshacerParcial($parcial, $areaOperario, $estadoOriginal);

                return new ReciboCommandResultDTO(true, 'Marca de completado del parcial eliminada', 200);
            }

            if ($esCortador) {
                $recibo = $this->recibos->findActiveById((int) $idRecibo);

                if ($recibo) {
                    $areaRecibo = strtolower(trim((string) ($recibo->area ?? '')));
                    if ($areaRecibo === 'costura') {
                        $sinEncargadoCostura = true;
                        $procesoCostura = null;

                        if (!empty($recibo->prenda_id)) {
                            $numeroPedido = $this->workflow->findNumeroPedidoByPrendaId((int) $recibo->prenda_id);

                            $procesoCostura = !empty($numeroPedido)
                                ? $this->procesos->findLatestByProcesoAndNumeroRecibo(
                                    numeroPedido: (int) $numeroPedido,
                                    prendaId: (int) $recibo->prenda_id,
                                    proceso: 'Costura',
                                    numeroRecibo: (int) $recibo->consecutivo_actual,
                                )
                                : $this->procesos->findLatestByPrendaAndProceso((int) $recibo->prenda_id, 'Costura');
                            if ($procesoCostura && !empty($procesoCostura->encargado)) {
                                $sinEncargadoCostura = false;
                            }

                            // Fallback
                            if ($sinEncargadoCostura && empty($procesoCostura)) {
                                $procesoCosturaFallback = !empty($numeroPedido)
                                    ? $this->procesos->findLatestByProceso(
                                        numeroPedido: (int) $numeroPedido,
                                        prendaId: (int) $recibo->prenda_id,
                                        proceso: 'Costura',
                                    )
                                    : $this->procesos->findLatestByPrendaAndProceso((int) $recibo->prenda_id, 'Costura');

                                if ($procesoCosturaFallback && !empty($procesoCosturaFallback->encargado)) {
                                    $sinEncargadoCostura = false;
                                }

                                if (empty($procesoCostura) && !empty($procesoCosturaFallback)) {
                                    $procesoCostura = $procesoCosturaFallback;
                                }
                            }
                        }

                        if ($sinEncargadoCostura) {
                            $recibo->area = 'Corte';
                            $this->recibos->save($recibo);

                            if (!empty($procesoCostura)) {
                                $this->procesos->forceDelete($procesoCostura);
                            }
                        }
                    }
                }
            }

            $this->workflow->deleteCompletadoByReciboAndArea((int) $idRecibo, $areaOperario);

            try {
                $recibo = $this->recibos->findActiveById((int) $idRecibo);
                if ($recibo) {
                    $this->notificarVistaCosturaDeshacerRecibo(
                        reciboId: (int) $recibo->id,
                        consecutivo: (string) ($recibo->consecutivo_actual ?? '0'),
                        pedidoId: (int) ($recibo->pedido_produccion_id ?? 0),
                        prendaId: $recibo->prenda_id ? (int) $recibo->prenda_id : null,
                        tipoRecibo: (string) ($recibo->tipo_recibo ?? ''),
                        mensaje: "El recibo #{$recibo->consecutivo_actual} fue deshecho",
                        esParcial: false,
                        pedidoParcialId: null,
                        consecutivoParcial: null,
                        originalCompletado: false
                    );
                }
            } catch (\Exception $e) {
                \Log::warning('[OperarioController] Error al broadcast deshacer a vista-costura', [
                    'recibo_id' => (int) $idRecibo,
                    'error' => $e->getMessage(),
                ]);
            }

            return new ReciboCommandResultDTO(true, 'Marca de completado eliminada', 200);
        } catch (\Exception $e) {
            \Log::error('Error al deshacer recibo: ' . $e->getMessage(), [
                'id_recibo' => $idRecibo,
                'exception' => $e,
            ]);

            return new ReciboCommandResultDTO(false, 'Error al deshacer el recibo', 500);
        }
    }

    private function sincronizarCompletadoOriginalDesdeParciales(ReciboPorPartes $parcial, string $areaOperario): array
    {
        $parcialIds = $this->workflow->findParcialIdsForOriginal($parcial);

        $reciboOriginal = $this->workflow->findReciboOriginalActivoDesdeParcial($parcial);

        if (!$reciboOriginal) {
            return ['original_completado' => false, 'recibo_original_id' => null];
        }

        $totalCompletados = $this->workflow->countCompletadosParcialesByArea($parcialIds, $areaOperario);

        if ($totalCompletados < count($parcialIds)) {
            $this->workflow->deleteCompletadoByReciboAndArea((int) $reciboOriginal->id, $areaOperario);
        }

        return [
            'original_completado' => $totalCompletados >= count($parcialIds),
            'recibo_original_id' => (int) $reciboOriginal->id,
        ];
    }

    private function notificarVistaCosturaDeshacerParcial(ReciboPorPartes $parcial, string $areaOperario, array $estadoOriginal): void
    {
        $usuariosVistaCostura = $this->workflow->findVistaCosturaUsers();

        foreach ($usuariosVistaCostura as $usuarioVistaCostura) {
            broadcast(new OperarioRecibosActualizados(
                userId: (int) $usuarioVistaCostura->id,
                payload: [
                    'area' => $areaOperario,
                    'accion' => 'recibo_deshecho',
                    'es_parcial' => true,
                    'pedido_parcial_id' => (int) $parcial->id,
                    'id_parcial' => (int) $parcial->id,
                    'recibo_id' => (int) $parcial->id,
                    'consecutivo' => (string) $parcial->consecutivo_parcial,
                    'consecutivo_parcial' => (string) $parcial->consecutivo_parcial,
                    'consecutivo_original' => (string) $parcial->consecutivo_original,
                    'pedido_produccion_id' => (int) $parcial->pedido_produccion_id,
                    'prenda_id' => (int) $parcial->prenda_pedido_id,
                    'tipo_recibo' => (string) ($parcial->tipo_recibo ?: 'PARCIAL'),
                    'original_completado' => (bool) ($estadoOriginal['original_completado'] ?? false),
                    'recibo_original_id' => $estadoOriginal['recibo_original_id'] ?? null,
                    'mensaje' => "El parcial #{$parcial->consecutivo_parcial} fue deshecho",
                ]
            ));
        }
    }

    private function notificarVistaCosturaDeshacerRecibo(
        int $reciboId,
        string $consecutivo,
        int $pedidoId,
        ?int $prendaId,
        string $tipoRecibo,
        string $mensaje,
        bool $esParcial,
        ?int $pedidoParcialId,
        ?string $consecutivoParcial,
        bool $originalCompletado
    ): void {
        $usuariosVistaCostura = $this->workflow->findVistaCosturaUsers();

        foreach ($usuariosVistaCostura as $usuarioVistaCostura) {
            broadcast(new OperarioRecibosActualizados(
                userId: (int) $usuarioVistaCostura->id,
                payload: [
                    'area' => 'Costura',
                    'accion' => 'recibo_deshecho',
                    'es_parcial' => $esParcial,
                    'recibo_id' => $reciboId,
                    'pedido_parcial_id' => $pedidoParcialId,
                    'consecutivo' => $consecutivo,
                    'consecutivo_parcial' => $consecutivoParcial,
                    'pedido_produccion_id' => $pedidoId,
                    'prenda_id' => $prendaId,
                    'tipo_recibo' => $tipoRecibo,
                    'original_completado' => $originalCompletado,
                    'mensaje' => $mensaje,
                ]
            ));
        }
    }
}
