<?php

namespace App\Application\ProcesoSeguimiento\DTOs;

use App\Infrastructure\Http\Requests\ActualizarProcesoSeguimientoRequest;

/**
 * DTO: ActualizarProcesoSeguimientoDTO
 *
 * Transporta los datos validados hacia ActualizarProcesoSeguimientoUseCase.
 */
final class ActualizarProcesoSeguimientoDTO
{
    public function __construct(
        public readonly int     $procesoId,
        public readonly string  $area,
        public readonly string  $estado,
        public readonly ?string $fechaInicio,
        public readonly ?string $encargado,
        public readonly ?string $observaciones,
    ) {}

    public static function fromRequest(ActualizarProcesoSeguimientoRequest $request, int $procesoId): self
    {
        return new self(
            procesoId:     $procesoId,
            area:          (string) $request->area,
            estado:        (string) $request->estado,
            fechaInicio:   $request->fecha_inicio,
            encargado:     $request->encargado,
            observaciones: $request->observaciones,
        );
    }
}
