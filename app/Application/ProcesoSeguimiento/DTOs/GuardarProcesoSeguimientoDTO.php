<?php

namespace App\Application\ProcesoSeguimiento\DTOs;

use App\Infrastructure\Http\Requests\GuardarProcesoSeguimientoRequest;

/**
 * DTO: GuardarProcesoSeguimientoDTO
 *
 * Transporta los datos validados del request hacia el Use Case,
 * desacoplando la capa de infraestructura (HTTP) de la de aplicación.
 */
final class GuardarProcesoSeguimientoDTO
{
    public function __construct(
        public readonly int    $pedidoProduccionId,
        public readonly int    $prendaId,
        public readonly string $area,
        public readonly string $estado,
        public readonly string $encargado,
        public readonly ?string $observaciones,
    ) {}

    public static function fromRequest(GuardarProcesoSeguimientoRequest $request): self
    {
        return new self(
            pedidoProduccionId: (int) $request->pedido_produccion_id,
            prendaId:           (int) $request->prenda_id,
            area:               (string) $request->area,
            estado:             (string) $request->estado,
            encargado:          (string) $request->encargado,
            observaciones:      $request->observaciones,
        );
    }
}
