<?php

namespace App\Application\ProcesoSeguimiento\UseCases;

use App\Domain\ProcesoSeguimiento\Repositories\ProcesoPrendaSeguimientoRepository;
use App\Models\ProcesoPrenda;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: ActualizarEstadoProcesoUseCase
 *
 * Actualiza únicamente el estado (y observaciones) de un proceso existente.
 * Registra fecha_fin y dias_duracion cuando el estado pasa a Completado.
 */
final class ActualizarEstadoProcesoUseCase
{
    public function __construct(
        private readonly ProcesoPrendaSeguimientoRepository $procesosRepo,
    ) {}
    public function execute(int $procesoId, string $estado, ?string $observaciones): ProcesoPrenda
    {
        $proceso = ProcesoPrenda::findOrFail($procesoId);

        $proceso->estado_proceso  = $estado;
        $proceso->observaciones   = $observaciones;

        if ($estado === 'Completado' && !$proceso->fecha_fin) {
            $proceso->fecha_fin = now();

            if ($proceso->fecha_inicio) {
                $dias = $proceso->fecha_inicio->diffInDays(now());
                $proceso->dias_duracion = $dias > 0 ? $dias . ' días' : 'Menos de 1 día';
            }
        }

        $this->procesosRepo->guardar($proceso);
        $proceso->load(['prenda', 'pedido']);

        Log::info('[ActualizarEstadoProcesoUseCase] Estado actualizado', [
            'proceso_id' => $procesoId,
            'estado'     => $estado,
        ]);

        return $proceso;
    }
}
