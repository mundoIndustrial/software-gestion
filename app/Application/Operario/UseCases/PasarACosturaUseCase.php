<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\PasarACosturaCommandDTO;
use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Domain\Operario\Services\ControlCalidadWorkflow;
use App\Events\EncargadoCosturaAsignado;
use App\Events\OperarioRecibosActualizados;
use App\Events\ReciboAsignadoCosturero;
use Illuminate\Support\Facades\Log;

class PasarACosturaUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly ProcesoPrendaRepository $procesos,
        private readonly ControlCalidadWorkflow $workflowService,
    ) {}

    public function execute(PasarACosturaCommandDTO $cmd): ReciboCommandResultDTO
    {
        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                return new ReciboCommandResultDTO(false, 'No tienes permisos para realizar esta accion', 403);
            }

            $pedido = $this->workflowService->findPedidoOrFail($cmd->pedidoId);

            Log::info('[COSTURA] Buscando recibo para pasar a Costura', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $cmd->prendaId,
                'tipo_recibo' => $cmd->tipoRecibo,
                'numero_recibo' => $cmd->numeroRecibo,
                'encargado' => $cmd->encargado,
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
                return new ReciboCommandResultDTO(false, 'Recibo no encontrado', 404);
            }

            $prenda = $this->workflowService->findPrendaById($cmd->prendaId);

            [$nuevoProceso, $areaAnterior] = $this->workflowService->runInTransaction(function () use ($pedido, $cmd, $recibo) {
                $areaAnterior = $recibo->area;

                $procesoExistente = $this->procesos->findLatestByProceso(
                    numeroPedido: (int) $pedido->numero_pedido,
                    prendaId: (int) $cmd->prendaId,
                    proceso: 'Costura',
                );

                if ($procesoExistente) {
                    $datosActualizacion = [
                        'encargado' => $cmd->encargado,
                        'estado_proceso' => $procesoExistente->estado_proceso === 'Pendiente' ? 'En Progreso' : $procesoExistente->estado_proceso,
                    ];

                    if (trim($procesoExistente->encargado ?? '') !== trim($cmd->encargado) || !$procesoExistente->fecha_de_asignacion_encargado) {
                        $datosActualizacion['fecha_de_asignacion_encargado'] = now();
                    }

                    $this->procesos->update($procesoExistente, $datosActualizacion);
                    $nuevoProceso = $procesoExistente;
                } else {
                    $nuevoProceso = $this->procesos->create([
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_pedido_id' => $cmd->prendaId,
                        'numero_recibo' => $recibo->consecutivo_actual,
                        'proceso' => 'Costura',
                        'fecha_inicio' => now(),
                        'encargado' => $cmd->encargado,
                        'fecha_de_asignacion_encargado' => now(),
                        'estado_proceso' => 'En Progreso',
                        'codigo_referencia' => 'COS-' . $recibo->consecutivo_actual . '-' . date('YmdHis'),
                    ]);

                    $recibo->area = 'Costura';
                    $this->recibos->save($recibo);
                }

                return [$nuevoProceso, $areaAnterior];
            });

            $encargadoUsuario = $this->workflowService->findUserByNormalizedName((string) $cmd->encargado);
            $encargadoRol = null;
            try {
                $encargadoRol = $encargadoUsuario?->roles?->first()?->name;
            } catch (\Exception $e) {
                $encargadoRol = null;
            }

            broadcast(new EncargadoCosturaAsignado(
                $pedido->id,
                $cmd->prendaId,
                $recibo->consecutivo_actual,
                $cmd->encargado,
                $nuevoProceso->id,
                $prenda?->nombre_prenda ?? 'Prenda sin nombre',
                optional($nuevoProceso->updated_at)->toIso8601String(),
                (int) $recibo->id,
                (string) ($pedido->cliente ?? '-'),
                $encargadoRol
            ));

            broadcast(new ReciboAsignadoCosturero(
                $pedido->id,
                $cmd->prendaId,
                $recibo->consecutivo_actual,
                $prenda?->nombre_prenda ?? 'Prenda sin nombre',
                $cmd->encargado,
                $nuevoProceso->id,
                $cmd->encargado
            ));

            $encargadoNormalizado = strtolower(trim((string) $cmd->encargado));
            if ($encargadoNormalizado !== '') {
                $operarioAsignado = $this->workflowService->findUserByNormalizedName($encargadoNormalizado);

                if ($operarioAsignado && ($operarioAsignado->hasRole('costura-reflectivo') || $operarioAsignado->hasRole('lider-reflectivo'))) {
                    broadcast(new OperarioRecibosActualizados(
                        userId: (int) $operarioAsignado->id,
                        payload: [
                            'area' => 'Costura',
                            'accion' => 'asignado',
                            'numero_pedido' => (int) $pedido->id,
                            'prenda_id' => (int) $cmd->prendaId,
                            'proceso_id' => (int) $nuevoProceso->id,
                            'tipo_recibo' => (string) $recibo->tipo_recibo,
                            'numero_recibo' => (int) $recibo->consecutivo_actual,
                            'encargado' => (string) $cmd->encargado,
                            'mensaje' => "Se te asigno el recibo #{$recibo->consecutivo_actual} de {$recibo->tipo_recibo}",
                        ]
                    ));

                    Log::info('[COSTURA] Broadcast a costura-reflectivo/lider-reflectivo (asignado)', [
                        'user_id' => $operarioAsignado->id,
                        'rol' => $operarioAsignado->roles->first()->name ?? 'sin rol',
                        'recibo' => $recibo->consecutivo_actual,
                        'tipo_recibo' => $recibo->tipo_recibo,
                    ]);
                }
            }

            $tipoReciboUpper = strtoupper(trim((string) $recibo->tipo_recibo));

            Log::info('[COSTURA] Verificando tipo de recibo para broadcast', [
                'tipo_recibo_original' => $recibo->tipo_recibo,
                'tipo_recibo_upper' => $tipoReciboUpper,
            ]);

            if ($tipoReciboUpper === 'REFLECTIVO') {
                $usuariosReflectivos = $this->workflowService->findUsersWithAnyRole(['costura-reflectivo', 'lider-reflectivo']);

                Log::info('[COSTURA] Broadcast REFLECTIVO a todos los costura-reflectivo/lider-reflectivo', [
                    'total_usuarios' => $usuariosReflectivos->count(),
                    'usuario_ids' => $usuariosReflectivos->pluck('id')->toArray(),
                    'recibo' => $recibo->consecutivo_actual,
                    'encargado' => $cmd->encargado,
                ]);

                foreach ($usuariosReflectivos as $usuarioReflectivo) {
                    broadcast(new OperarioRecibosActualizados(
                        userId: (int) $usuarioReflectivo->id,
                        payload: [
                            'area' => 'Costura',
                            'accion' => 'recibo_asignado_reflectivo',
                            'numero_pedido' => (int) $pedido->id,
                            'prenda_id' => (int) $cmd->prendaId,
                            'proceso_id' => (int) $nuevoProceso->id,
                            'tipo_recibo' => (string) $recibo->tipo_recibo,
                            'numero_recibo' => (int) $recibo->consecutivo_actual,
                            'encargado' => (string) $cmd->encargado,
                            'mensaje' => "El recibo #{$recibo->consecutivo_actual} de REFLECTIVO fue asignado a {$cmd->encargado}",
                        ]
                    ));

                    Log::info('[COSTURA] Broadcast REFLECTIVO enviado a usuario', [
                        'user_id' => $usuarioReflectivo->id,
                        'user_name' => $usuarioReflectivo->name,
                    ]);
                }
            }

            if ($tipoReciboUpper === 'COSTURA' || $tipoReciboUpper === 'COSTURA-BODEGA') {
                $notificarLideres = $encargadoUsuario && $encargadoUsuario->hasRole('costura-reflectivo');

                if ($notificarLideres) {
                    $usuariosLiderReflectivo = $this->workflowService->findUsersWithAnyRole(['lider-reflectivo']);

                    Log::info('[COSTURA] Broadcast COSTURA a lider-reflectivo (encargado es costura-reflectivo)', [
                        'total_usuarios' => $usuariosLiderReflectivo->count(),
                        'usuario_ids' => $usuariosLiderReflectivo->pluck('id')->toArray(),
                        'recibo' => $recibo->consecutivo_actual,
                        'encargado' => $cmd->encargado,
                        'encargado_tiene_rol_costura_reflectivo' => true,
                    ]);

                    foreach ($usuariosLiderReflectivo as $usuarioLider) {
                        broadcast(new OperarioRecibosActualizados(
                            userId: (int) $usuarioLider->id,
                            payload: [
                                'area' => 'Costura',
                                'accion' => 'recibo_asignado_costura',
                                'numero_pedido' => (int) $pedido->id,
                                'prenda_id' => (int) $cmd->prendaId,
                                'proceso_id' => (int) $nuevoProceso->id,
                                'tipo_recibo' => (string) $recibo->tipo_recibo,
                                'numero_recibo' => (int) $recibo->consecutivo_actual,
                                'encargado' => (string) $cmd->encargado,
                                'mensaje' => "El recibo #{$recibo->consecutivo_actual} de COSTURA fue asignado a {$cmd->encargado}",
                            ]
                        ));

                        Log::info('[COSTURA] Broadcast COSTURA enviado a lider-reflectivo', [
                            'user_id' => $usuarioLider->id,
                            'user_name' => $usuarioLider->name,
                        ]);
                    }
                } else {
                    Log::info('[COSTURA] NO se notifica a lider-reflectivo (encargado no tiene rol costura-reflectivo)', [
                        'recibo' => $recibo->consecutivo_actual,
                        'encargado' => $cmd->encargado,
                        'encargado_existe' => !empty($encargadoUsuario),
                        'encargado_tiene_rol_costura_reflectivo' => false,
                    ]);
                }
            }

            Log::info('Recibo enviado a Costura', [
                'pedido_id' => $cmd->pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $cmd->prendaId,
                'numero_recibo' => $recibo->consecutivo_actual,
                'proceso_id' => $nuevoProceso->id,
                'encargado' => $cmd->encargado,
                'usuario_id' => auth()->id(),
            ]);

            return new ReciboCommandResultDTO(true, 'Recibo enviado a Costura correctamente', 200, [
                'proceso_id' => $nuevoProceso->id,
                'proceso_nombre' => 'Costura',
                'encargado' => $cmd->encargado,
                'area_anterior' => $areaAnterior,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al pasar recibo a Costura', [
                'pedido_id' => $cmd->pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new ReciboCommandResultDTO(false, 'Error al pasar a Costura: ' . $e->getMessage(), 500);
        }
    }
}
