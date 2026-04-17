<?php

namespace App\Application\ProcesoSeguimiento\UseCases;

use App\Application\ProcesoSeguimiento\DTOs\GuardarProcesoSeguimientoDTO;
use App\Application\ProcesoSeguimiento\Services\ProcesoSeguimientoBroadcastService;
use App\Domain\ProcesoSeguimiento\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\ProcesoSeguimiento\Repositories\ProcesoPrendaSeguimientoRepository;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
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
    public function __construct(
        private readonly ProcesoSeguimientoBroadcastService   $broadcastService,
        private readonly ProcesoPrendaSeguimientoRepository   $procesosRepo,
        private readonly ConsecutivoReciboPedidoRepository    $consecutivosRepo,
    ) {}

    /**
     * @return GuardarProcesoSeguimientoResultado
     */
    public function execute(GuardarProcesoSeguimientoDTO $dto): GuardarProcesoSeguimientoResultado
    {
        // ── 1. Upsert por área ─────────────────────────────────────────────
        $procesoExistente = $this->procesosRepo->encontrarActivoPorArea(
            $dto->pedidoProduccionId, $dto->prendaId, $dto->area, $dto->numeroRecibo
        );

        if ($procesoExistente) {
            $encargadoAnterior = (string) ($procesoExistente->encargado ?? '');
            $encargadoNuevo    = (string) ($dto->encargado ?? '');

            $procesoExistente->estado_proceso = $dto->estado;
            $procesoExistente->encargado      = $dto->encargado;
            $procesoExistente->observaciones  = $dto->observaciones ?? $procesoExistente->observaciones;

            if ($encargadoNuevo !== '' && $encargadoNuevo !== $encargadoAnterior) {
                $procesoExistente->fecha_de_asignacion_encargado = now();
            }
            $this->procesosRepo->guardar($procesoExistente);
            $proceso = $procesoExistente;
            $accion  = 'actualizado';
        } else {
            // Usar numero_recibo del DTO si se proporciona, si no obtener del consecutivo
            $numeroRecibo = $dto->numeroRecibo;
            if ($numeroRecibo === null && $dto->area === 'Control de Calidad') {
                $numeroRecibo = $this->obtenerNumeroReciboCostura($dto->pedidoProduccionId, $dto->prendaId);
            }

            $nuevo = new ProcesoPrenda([
                'numero_pedido'     => $dto->pedidoProduccionId,
                'prenda_pedido_id'  => $dto->prendaId,
                'numero_recibo'     => $numeroRecibo,
                'proceso'           => $dto->area,
                'fecha_inicio'      => now(),
                'estado_proceso'    => $dto->estado,
                'encargado'         => $dto->encargado,
                'fecha_de_asignacion_encargado' => ((string) ($dto->encargado ?? '')) !== '' ? now() : null,
                'observaciones'     => $dto->observaciones,
                'codigo_referencia' => $this->generarCodigoReferencia($dto->area, $dto->prendaId),
            ]);
            $proceso = $this->procesosRepo->guardar($nuevo);
            $accion  = 'creado';
        }

        Log::info('[GuardarProcesoSeguimientoUseCase] Proceso ' . $accion, [
            'proceso_id' => $proceso->id,
            'area'       => $proceso->proceso,
            'encargado'  => $proceso->encargado,
        ]);

        // ── 2. Broadcasts a operarios ──────────────────────────────────────
        $this->broadcastService->disparar(
            area:          $dto->area,
            encargado:     $dto->encargado,
            accion:        $accion,
            numeroPedido:  $dto->pedidoProduccionId,
            prendaId:      $dto->prendaId,
            procesoId:     $proceso->id,
        );

        // ── 3. Sincronizar consecutivo de recibos ──────────────────────────
        $this->sincronizarConsecutivo($dto);

        return new GuardarProcesoSeguimientoResultado($proceso, $accion);
    }

    // ── Helpers privados ────────────────────────────────────────────────────

    private function obtenerNumeroReciboCostura(int $pedidoProduccionId, int $prendaId): ?int
    {
        try {
            $consecutivo = $this->consecutivosRepo->encontrarPorPedidoYPrenda(
                $pedidoProduccionId, $prendaId
            );

            if ($consecutivo && $consecutivo->tipo_recibo === 'COSTURA') {
                return $consecutivo->consecutivo_actual;
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('[GuardarProcesoSeguimientoUseCase] Error obteniendo numero_recibo: ' . $e->getMessage());
            return null;
        }
    }

    private function generarCodigoReferencia(string $area, int $prendaId): string
    {
        $areaAbrev           = strtoupper(substr($area, 0, 3));
        $prendaIdFormateado  = str_pad($prendaId, 4, '0', STR_PAD_LEFT);
        $secuencial          = date('His');

        return $areaAbrev . '-' . $prendaIdFormateado . '-' . $secuencial;
    }

    private function sincronizarConsecutivo(GuardarProcesoSeguimientoDTO $dto): void
    {
        try {
            $prenda = PrendaPedido::find($dto->prendaId);

            if (!$prenda || !$prenda->pedido_produccion_id) {
                return;
            }

            $consecutivo = $this->consecutivosRepo->encontrarPorPedidoYPrenda(
                (int) $prenda->pedido_produccion_id, $dto->prendaId
            );

            if (!$consecutivo) {
                return;
            }

            $estado = $dto->area === 'Insumos' ? 'Pendiente_Insumos' : null;
            $this->consecutivosRepo->actualizarArea($consecutivo, $dto->area, $estado);
        } catch (\Exception $e) {
            Log::warning('[GuardarProcesoSeguimientoUseCase] Error sincronizando consecutivo: ' . $e->getMessage());
        }
    }
}
