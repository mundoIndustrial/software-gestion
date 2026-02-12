<?php

namespace App\Application\Services\Cotizacion;

use App\Models\TipoCotizacion;

final class ObtenerTipoCotizacionIdService
{
    public function ejecutar(string $tipo): int
    {
        $tipoCot = TipoCotizacion::firstOrCreate(
            ['codigo' => $tipo],
            ['nombre' => $tipo]
        );

        return $tipoCot->id;
    }
}
