<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Application\Operario\DTOs\NotificacionReciboCompletadoDTO;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Domain\Operario\Services\ReciboOperarioWorkflow;
use App\Events\OperarioRecibosActualizados;
use App\Events\ReciboCompletado;
use App\Models\ReciboPorPartes;
use Illuminate\Support\Facades\Auth;

class CompletarReciboOperarioUseCase
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

            $esCortador = $usuario->hasRole('cortador');
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
                return new ReciboCommandResultDTO(false, 'Solo los roles de Costura pueden completar parciales', 403);
            }

            if ($esParcial) {
                $parcial = $this->workflow->findParcialById((int) $idRecibo, true);

                if (!$parcial || !$parcial->pedido || !$parcial->prenda) {
                    return new ReciboCommandResultDTO(false, 'Parcial no encontrado', 404);
                }

                $nombreOperario = (string) $usuario->name;
                if ($esCosturaReflectivo || $esLiderReflectivo || $esAdminCostura) {
                    $procesoParcial = $this->buscarProcesoCosturaParcial($parcial);
                    $encargadoActual = is_string($procesoParcial?->encargado) ? trim($procesoParcial->encargado) : $procesoParcial?->encargado;

                    if (empty($encargadoActual)) {
                        return new ReciboCommandResultDTO(false, 'El parcial no tiene encargado de Costura asignado', 422);
                    }

                    $nombreOperario = (string) $encargadoActual;
                }

                $this->workflow->upsertCompletado(
                    idRecibo: null,
                    idParcial: (int) $parcial->id,
                    area: $areaOperario,
                    numeroRecibo: (string) $parcial->consecutivo_parcial,
                    nombreOperario: $nombreOperario
                );

                $estadoOriginal = $this->sincronizarCompletadoOriginalDesdeParciales($parcial, $areaOperario, $nombreOperario);
                $this->notificarVistaCosturaCompletadoParcial($parcial, $areaOperario, $nombreOperario, $estadoOriginal);

                return new ReciboCommandResultDTO(true, 'Parcial marcado como completado', 200);
            }

            $recibo = $this->recibos->findActiveById((int) $idRecibo);

            if (!$recibo) {
                return new ReciboCommandResultDTO(false, 'Recibo no encontrado', 404);
            }

            $areaRecibo = trim((string) ($recibo->area ?? ''));
            if (strcasecmp($areaRecibo, $areaOperario) !== 0) {
                return new ReciboCommandResultDTO(false, 'Este recibo no está en tu área actual', 403);
            }

            $nombreOperario = (string) $usuario->name;

            // Para costura-reflectivo, lider-reflectivo y administrador-costura: usar el nombre del encargado asignado
            if ($esCosturaReflectivo || $esLiderReflectivo || $esAdminCostura) {
                $encargadoActual = null;
                if (!empty($recibo->prenda_id)) {
                    $procesoCostura = $this->procesos->findLatestByPrendaAndProceso((int) $recibo->prenda_id, 'Costura');
                    $encargadoActual = $procesoCostura?->encargado;
                }

                $encargadoActual = is_string($encargadoActual) ? trim($encargadoActual) : $encargadoActual;
                if (empty($encargadoActual)) {
                    return new ReciboCommandResultDTO(false, 'El recibo no tiene encargado de Costura asignado', 422);
                }
                $nombreOperario = (string) $encargadoActual;
            }

            if ($esCortador) {
                $this->workflow->runInTransaction(function () use ($recibo) {
                    $recibo->area = 'Costura';
                    $this->recibos->save($recibo);

                    if (!empty($recibo->prenda_id)) {
                        $numeroPedido = $this->workflow->findNumeroPedidoByPrendaId((int) $recibo->prenda_id);

                        if (!empty($numeroPedido)) {
                            $procesoCostura = $this->procesos->findLatestByProceso(
                                numeroPedido: (int) $numeroPedido,
                                prendaId: (int) $recibo->prenda_id,
                                proceso: 'Costura',
                            );

                            if (!$procesoCostura) {
                                $this->procesos->create([
                                    'numero_pedido' => $numeroPedido,
                                    'prenda_pedido_id' => $recibo->prenda_id,
                                    'numero_recibo' => $recibo->consecutivo_actual,
                                    'proceso' => 'Costura',
                                    'fecha_inicio' => now(),
                                    'encargado' => null,
                                    'estado_proceso' => 'Pendiente',
                                    'codigo_referencia' => 'COS-' . ($recibo->consecutivo_actual ?? 0) . '-' . date('YmdHis'),
                                ]);
                            } else {
                                $this->procesos->update($procesoCostura, [
                                    'encargado' => null,
                                ]);
                            }
                        }
                    }
                });
            }

            $this->workflow->upsertCompletado(
                idRecibo: (int) $recibo->id,
                idParcial: null,
                area: $areaOperario,
                numeroRecibo: (string) ($recibo->consecutivo_actual ?? 0),
                nombreOperario: $nombreOperario
            );

            try {
                event(new ReciboCompletado([
                    'recibo_id' => (int) $recibo->id,
                    'consecutivo' => (int) ($recibo->consecutivo_actual ?? 0),
                    'pedido_produccion_id' => (int) ($recibo->pedido_produccion_id ?? 0),
                    'prenda_id' => $recibo->prenda_id ? (int) $recibo->prenda_id : null,
                    'tipo_recibo' => (string) ($recibo->tipo_recibo ?? ''),
                    'area' => (string) $areaOperario,
                    'nombre_operario' => (string) $nombreOperario,
                ]));

                $this->notificarVistaCosturaCompletadoRecibo(
                    new NotificacionReciboCompletadoDTO(
                        reciboId: (int) $recibo->id,
                        consecutivo: (string) ($recibo->consecutivo_actual ?? '0'),
                        pedidoId: (int) ($recibo->pedido_produccion_id ?? 0),
                        prendaId: $recibo->prenda_id ? (int) $recibo->prenda_id : null,
                        tipoRecibo: (string) ($recibo->tipo_recibo ?? ''),
                        nombreOperario: (string) $nombreOperario,
                        mensaje: "El recibo #{$recibo->consecutivo_actual} fue completado por {$nombreOperario}",
                        esParcial: false,
                        pedidoParcialId: null,
                        consecutivoParcial: null,
                        originalCompletado: true
                    )
                );
            } catch (\Exception $e) {
                \Log::warning('[OperarioController] Error al broadcast ReciboCompletado', [
                    'recibo_id' => (int) $idRecibo,
                    'error' => $e->getMessage(),
                ]);
            }

            return new ReciboCommandResultDTO(true, 'Recibo marcado como completado', 200);
        } catch (\Exception $e) {
            \Log::error('Error al completar recibo: ' . $e->getMessage(), [
                'id_recibo' => $idRecibo,
                'exception' => $e,
            ]);

            return new ReciboCommandResultDTO(false, 'Error al completar el recibo', 500);
        }
    }

    private function buscarProcesoCosturaParcial(ReciboPorPartes $parcial): ?\App\Models\ProcesoPrenda
    {
        $numeroPedido = (int) ($parcial->pedido?->numero_pedido ?? 0);
        if ($numeroPedido <= 0) {
            return null;
        }

        return $this->workflow->findProcesoCosturaParcial($parcial);
    }

    private function sincronizarCompletadoOriginalDesdeParciales(ReciboPorPartes $parcial, string $areaOperario, string $nombreOperario): array
    {
        $parcialIds = $this->workflow->findParcialIdsForOriginal($parcial);

        if (empty($parcialIds)) {
            return ['original_completado' => false, 'recibo_original_id' => null];
        }

        $reciboOriginal = $this->workflow->findReciboOriginalActivoDesdeParcial($parcial);

        if (!$reciboOriginal) {
            return ['original_completado' => false, 'recibo_original_id' => null];
        }

        $totalCompletados = $this->workflow->countCompletadosParcialesByArea($parcialIds, $areaOperario);
        $estaCompleto = $totalCompletados >= count($parcialIds);

        if ($estaCompleto) {
            $this->workflow->upsertCompletado(
                idRecibo: (int) $reciboOriginal->id,
                idParcial: null,
                area: $areaOperario,
                numeroRecibo: (string) $reciboOriginal->consecutivo_actual,
                nombreOperario: $nombreOperario
            );
        } else {
            $this->workflow->deleteCompletadoByReciboAndArea((int) $reciboOriginal->id, $areaOperario);
        }

        return [
            'original_completado' => $estaCompleto,
            'recibo_original_id' => (int) $reciboOriginal->id
        ];
    }

    private function notificarVistaCosturaCompletadoParcial(
        ReciboPorPartes $parcial,
        string $areaOperario,
        string $nombreOperario,
        array $estadoOriginal
    ): void {
        $usuariosVistaCostura = $this->workflow->findVistaCosturaUsers();

        foreach ($usuariosVistaCostura as $usuarioVistaCostura) {
            broadcast(new OperarioRecibosActualizados(
                userId: (int) $usuarioVistaCostura->id,
                payload: [
                    'area' => $areaOperario,
                    'accion' => 'recibo_completado',
                    'es_parcial' => true,
                    'pedido_parcial_id' => (int) $parcial->id,
                    'id_parcial' => (int) $parcial->id,
                    'recibo_id' => (int) $parcial->id,
                    'consecutivo' => (string) $this->formatearConsecutivoParcial($parcial->consecutivo_parcial),
                    'consecutivo_parcial' => (string) $this->formatearConsecutivoParcial($parcial->consecutivo_parcial),
                    'consecutivo_original' => (string) $this->formatearConsecutivoParcial($parcial->consecutivo_original),
                    'pedido_produccion_id' => (int) $parcial->pedido_produccion_id,
                    'prenda_id' => (int) $parcial->prenda_pedido_id,
                    'tipo_recibo' => (string) ($parcial->tipo_recibo ?: 'PARCIAL'),
                    'nombre_operario' => $nombreOperario,
                    'original_completado' => (bool) ($estadoOriginal['original_completado'] ?? false),
                    'recibo_original_id' => $estadoOriginal['recibo_original_id'] ?? null,
                    'mensaje' => "El parcial #{$this->formatearConsecutivoParcial($parcial->consecutivo_parcial)} fue completado por {$nombreOperario}",
                ]
            ));
        }
    }

    private function notificarVistaCosturaCompletadoRecibo(NotificacionReciboCompletadoDTO $notificacion): void
    {
        $usuariosVistaCostura = $this->workflow->findVistaCosturaUsers();

        foreach ($usuariosVistaCostura as $usuarioVistaCostura) {
            broadcast(new OperarioRecibosActualizados(
                userId: (int) $usuarioVistaCostura->id,
                payload: [
                    'area' => 'Costura',
                    'accion' => 'recibo_completado',
                    'es_parcial' => $notificacion->esParcial,
                    'recibo_id' => $notificacion->reciboId,
                    'pedido_parcial_id' => $notificacion->pedidoParcialId,
                    'consecutivo' => $notificacion->consecutivo,
                    'consecutivo_parcial' => $notificacion->consecutivoParcial,
                    'pedido_produccion_id' => $notificacion->pedidoId,
                    'prenda_id' => $notificacion->prendaId,
                    'tipo_recibo' => $notificacion->tipoRecibo,
                    'nombre_operario' => $notificacion->nombreOperario,
                    'original_completado' => $notificacion->originalCompletado,
                    'mensaje' => $notificacion->mensaje,
                ]
            ));
        }
    }

    private function formatearConsecutivoParcial($valor): string
    {
        $texto = trim((string) $valor);

        if ($texto === '') {
            return '0';
        }

        if (!is_numeric($texto)) {
            return $texto;
        }

        $numero = (float) $texto;
        if (abs($numero - (int) $numero) < 0.00001) {
            return (string) (int) $numero;
        }

        return rtrim(rtrim(number_format($numero, 2, '.', ''), '0'), '.');
    }
}
