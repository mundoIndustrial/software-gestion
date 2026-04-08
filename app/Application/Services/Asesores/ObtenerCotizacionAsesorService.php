<?php

namespace App\Application\Services\Asesores;

use App\Models\Cotizacion;

class ObtenerCotizacionAsesorService
{
    public function obtenerIdSiPerteneceAAsesor(int $cotizacionId, int $asesorId): ?int
    {
        $cotizacion = Cotizacion::query()
            ->select(['id', 'asesor_id'])
            ->find($cotizacionId);

        if (!$cotizacion) {
            return null;
        }

        if ((int) $cotizacion->asesor_id !== $asesorId) {
            return null;
        }

        return (int) $cotizacion->id;
    }
}
