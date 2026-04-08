<?php

namespace App\Application\ProcesoSeguimiento\UseCases;

use App\Application\ProcesoSeguimiento\DTOs\ActualizarProcesoSeguimientoDTO;
use App\Application\ProcesoSeguimiento\Services\ProcesoSeguimientoBroadcastService;
use App\Domain\ProcesoSeguimiento\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\ProcesoSeguimiento\Repositories\ProcesoPrendaSeguimientoRepository;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: ActualizarProcesoSeguimientoUseCase
 *
 * Actualiza completamente un proceso existente: área, estado, encargado,
 * fecha de inicio y observaciones. Registra fecha_fin/dias_duracion si se
 * marca como Completado. Dispara broadcasts y sincroniza el consecutivo.
 */
final class ActualizarProcesoSeguimientoUseCase
{
    public function __construct(
        private readonly ProcesoSeguimientoBroadcastService $broadcastService,
        private readonly ProcesoPrendaSeguimientoRepository $procesosRepo,
        private readonly ConsecutivoReciboPedidoRepository  $consecutivosRepo,
    ) {}

    public function execute(ActualizarProcesoSeguimientoDTO $dto): ProcesoPrenda
    {
        $proceso = ProcesoPrenda::findOrFail($dto->procesoId);

        $encargadoAnterior = (string) ($proceso->encargado ?? '');
        $encargadoNuevo    = (string) ($dto->encargado ?? '');

        // ── 1. Actualizar campos ───────────────────────────────────────────
        $proceso->proceso         = $dto->area;
        $proceso->estado_proceso  = $dto->estado;
        $proceso->encargado       = $dto->encargado;
        $proceso->observaciones   = $dto->observaciones;

        if ($encargadoNuevo !== '' && $encargadoNuevo !== $encargadoAnterior) {
            $proceso->fecha_de_asignacion_encargado = now();
        }

        if ($dto->fechaInicio) {
            $proceso->fecha_inicio = $dto->fechaInicio;
        }

        // Registrar fecha de fin y duración al completar
        if ($dto->estado === 'Completado' && !$proceso->fecha_fin) {
            $proceso->fecha_fin = now();

            if ($proceso->fecha_inicio) {
                $dias = $proceso->fecha_inicio->diffInDays(now());
                $proceso->dias_duracion = $dias > 0 ? $dias . ' días' : 'Menos de 1 día';
            }
        }

        $this->procesosRepo->guardar($proceso);

        Log::info('[ActualizarProcesoSeguimientoUseCase] Proceso actualizado', [
            'proceso_id' => $dto->procesoId,
            'area'       => $dto->area,
            'estado'     => $dto->estado,
            'encargado'  => $dto->encargado,
        ]);

        // ── 2. Broadcasts ─────────────────────────────────────────────────
        $this->broadcastService->disparar(
            area:         $dto->area,
            encargado:    $dto->encargado ?? '',
            accion:       'actualizado',
            numeroPedido: (int) ($proceso->numero_pedido ?? 0),
            prendaId:     (int) ($proceso->prenda_pedido_id ?? 0),
            procesoId:    $proceso->id,
        );

        // ── 3. Sincronizar consecutivo ────────────────────────────────────
        $this->sincronizarConsecutivo($dto->area, (int) ($proceso->prenda_pedido_id ?? 0));

        return $proceso;
    }

    private function sincronizarConsecutivo(string $area, int $prendaId): void
    {
        try {
            $prenda = PrendaPedido::find($prendaId);

            if (!$prenda || !$prenda->pedido_produccion_id) {
                return;
            }

            $consecutivo = $this->consecutivosRepo->encontrarPorPedidoYPrenda(
                (int) $prenda->pedido_produccion_id, $prendaId
            );

            if (!$consecutivo) {
                return;
            }

            $estado = $area === 'Insumos' ? 'Pendiente_Insumos' : null;
            $this->consecutivosRepo->actualizarArea($consecutivo, $area, $estado);
        } catch (\Exception $e) {
            Log::warning('[ActualizarProcesoSeguimientoUseCase] Error sincronizando consecutivo: ' . $e->getMessage());
        }
    }
}
