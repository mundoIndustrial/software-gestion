<?php

namespace App\Application\ProcesoSeguimiento\UseCases;

use App\Models\ProcesoPrenda;

/**
 * Resultado del Use Case GuardarProcesoSeguimientoUseCase.
 *
 * Encapsula el proceso persistido y la acción realizada,
 * evitando que el controller dependa directamente del modelo Eloquent
 * para interpretar el resultado.
 */
final class GuardarProcesoSeguimientoResultado
{
    public function __construct(
        public readonly ProcesoPrenda $proceso,
        /** 'creado' | 'actualizado' */
        public readonly string $accion,
    ) {}
}
