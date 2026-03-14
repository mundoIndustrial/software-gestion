<?php

namespace App\Application\ProcesoSeguimiento\UseCases;

use App\Application\ProcesoSeguimiento\DTOs\GuardarProcesoSeguimientoDTO;
use App\Events\CorteAsignadoOperario;
use App\Events\OperarioRecibosActualizados;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: GuardarProcesoSeguimientoUseCase
 *
 * Orquesta la lógica de negocio para crear o actualizar (upsert) un proceso de
 * seguimiento para una prenda, notificar a operarios vía broadcast y sincronizar
 * el consecutivo de recibos correspondiente.
 *
 * Responsabilidades:
 *  - Upsert por área (no duplica la misma área si no está Completado)
 *  - Generar código de referencia único
 *  - Disparar broadcasts a cortadores y costura-reflectivo
 *  - Sincronizar área (y estado Pendiente_Insumos cuando aplica) en consecutivos
 *
 * @see GuardarProcesoSeguimientoDTO  (entrada)
 * @see GuardarProcesoSeguimientoResultado  (salida)
 */
final class GuardarProcesoSeguimientoUseCase
{
    /**
     * @return GuardarProcesoSeguimientoResultado
     */
    public function execute(GuardarProcesoSeguimientoDTO $dto): GuardarProcesoSeguimientoResultado
    {
        // ── 1. Upsert por área ─────────────────────────────────────────────
        $procesoExistente = ProcesoPrenda::where([
            ['numero_pedido',   '=', $dto->pedidoProduccionId],
            ['prenda_pedido_id','=', $dto->prendaId],
            ['proceso',         '=', $dto->area],
            ['estado_proceso',  '!=', 'Completado'],
        ])->first();

        if ($procesoExistente) {
            $procesoExistente->update([
                'estado_proceso' => $dto->estado,
                'encargado'      => $dto->encargado,
                'observaciones'  => $dto->observaciones ?? $procesoExistente->observaciones,
                'fecha_inicio'   => $procesoExistente->fecha_inicio,
            ]);
            $proceso = $procesoExistente;
            $accion  = 'actualizado';
        } else {
            $proceso = ProcesoPrenda::create([
                'numero_pedido'    => $dto->pedidoProduccionId,
                'prenda_pedido_id' => $dto->prendaId,
                'proceso'          => $dto->area,
                'fecha_inicio'     => now(),
                'estado_proceso'   => $dto->estado,
                'encargado'        => $dto->encargado,
                'observaciones'    => $dto->observaciones,
                'codigo_referencia'=> $this->generarCodigoReferencia($dto->area, $dto->prendaId),
            ]);
            $accion = 'creado';
        }

        Log::info('[GuardarProcesoSeguimientoUseCase] Proceso ' . $accion, [
            'proceso_id' => $proceso->id,
            'area'       => $proceso->proceso,
            'encargado'  => $proceso->encargado,
        ]);

        // ── 2. Broadcasts a operarios ──────────────────────────────────────
        $this->dispararBroadcasts($dto, $proceso, $accion);

        // ── 3. Sincronizar consecutivo de recibos ──────────────────────────
        $this->sincronizarConsecutivo($dto);

        return new GuardarProcesoSeguimientoResultado($proceso, $accion);
    }

    // ── Helpers privados ────────────────────────────────────────────────────

    private function generarCodigoReferencia(string $area, int $prendaId): string
    {
        $areaAbrev           = strtoupper(substr($area, 0, 3));
        $prendaIdFormateado  = str_pad($prendaId, 4, '0', STR_PAD_LEFT);
        $secuencial          = date('His');

        return $areaAbrev . '-' . $prendaIdFormateado . '-' . $secuencial;
    }

    private function dispararBroadcasts(GuardarProcesoSeguimientoDTO $dto, ProcesoPrenda $proceso, string $accion): void
    {
        try {
            $areaNormalizada      = strtolower(trim($dto->area));
            $encargadoNormalizado = strtolower(trim($dto->encargado));

            if ($areaNormalizada === 'corte') {
                broadcast(new CorteAsignadoOperario([
                    'area'          => $dto->area,
                    'accion'        => $accion,
                    'numero_pedido' => $dto->pedidoProduccionId,
                    'prenda_id'     => $dto->prendaId,
                    'proceso_id'    => $proceso->id,
                    'encargado'     => $dto->encargado,
                ]));

                if ($encargadoNormalizado !== '') {
                    $operario = User::query()
                        ->whereRaw('LOWER(TRIM(name)) = ?', [$encargadoNormalizado])
                        ->first();

                    if ($operario && $operario->hasRole('cortador')) {
                        broadcast(new OperarioRecibosActualizados(
                            userId: $operario->id,
                            payload: [
                                'area'          => $dto->area,
                                'accion'        => $accion,
                                'numero_pedido' => $dto->pedidoProduccionId,
                                'prenda_id'     => $dto->prendaId,
                                'proceso_id'    => $proceso->id,
                            ]
                        ));
                    }
                }
            }

            if ($areaNormalizada === 'costura' && $encargadoNormalizado !== '') {
                $operario = User::query()
                    ->whereRaw('LOWER(TRIM(name)) = ?', [$encargadoNormalizado])
                    ->first();

                if ($operario && $operario->hasRole('costura-reflectivo')) {
                    broadcast(new OperarioRecibosActualizados(
                        userId: $operario->id,
                        payload: [
                            'area'          => $dto->area,
                            'accion'        => $accion,
                            'numero_pedido' => $dto->pedidoProduccionId,
                            'prenda_id'     => $dto->prendaId,
                            'proceso_id'    => $proceso->id,
                        ]
                    ));
                }
            }
        } catch (\Exception $e) {
            // El fallo de broadcast no debe interrumpir la operación principal.
            Log::warning('[GuardarProcesoSeguimientoUseCase] Error en broadcast: ' . $e->getMessage());
        }
    }

    private function sincronizarConsecutivo(GuardarProcesoSeguimientoDTO $dto): void
    {
        try {
            $prenda = PrendaPedido::find($dto->prendaId);

            if (!$prenda || !$prenda->pedido_produccion_id) {
                return;
            }

            $consecutivo = ConsecutivoReciboPedido::where('pedido_produccion_id', $prenda->pedido_produccion_id)
                ->where('prenda_id', $dto->prendaId)
                ->first();

            if (!$consecutivo) {
                return;
            }

            $datos = ['area' => $dto->area];

            if ($dto->area === 'Insumos') {
                $datos['estado'] = 'Pendiente_Insumos';
            }

            $consecutivo->update($datos);
        } catch (\Exception $e) {
            // El fallo de sincronización no debe interrumpir la operación principal.
            Log::warning('[GuardarProcesoSeguimientoUseCase] Error sincronizando consecutivo: ' . $e->getMessage());
        }
    }
}
