<?php

namespace App\Application\Services\Cotizacion;

use App\Models\Cotizacion;
use App\Models\LogoCotizacion;
use App\Models\PrendaCot;
use Illuminate\Support\Facades\Log;

final class EliminarBorradorCotizacionService
{
    public function ejecutar(Cotizacion $cotizacion, int $asesorId): void
    {
        if ($cotizacion->asesor_id !== $asesorId) {
            throw new \DomainException('No tienes permiso');
        }

        if (!$cotizacion->es_borrador) {
            throw new \DomainException('Solo se pueden eliminar borradores');
        }

        Log::info('Eliminando borrador', ['cotizacion_id' => $cotizacion->id, 'asesor_id' => $asesorId]);

        PrendaCot::where('cotizacion_id', $cotizacion->id)->delete();
        LogoCotizacion::where('cotizacion_id', $cotizacion->id)->delete();
        $cotizacion->delete();

        Log::info('Borrador eliminado correctamente', ['cotizacion_id' => $cotizacion->id]);
    }
}
