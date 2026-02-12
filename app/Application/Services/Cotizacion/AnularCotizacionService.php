<?php

namespace App\Application\Services\Cotizacion;

use App\Models\Cotizacion;
use Illuminate\Support\Facades\Log;

final class AnularCotizacionService
{
    public function ejecutar(Cotizacion $cotizacion, int $asesorId, string $novedad, string $nombreUsuario): Cotizacion
    {
        if ($cotizacion->asesor_id !== $asesorId) {
            throw new \DomainException('No tienes permiso para anular esta cotización');
        }

        $fechaHora = now()->format('d-m-Y h:i:s A');
        $nuevaNovedad = "[{$nombreUsuario} - {$fechaHora}] {$novedad}";

        $novedadesActuales = $cotizacion->novedades ?? '';
        $novedadesActualizadas = trim($novedadesActuales) !== ''
            ? $novedadesActuales . "\n" . $nuevaNovedad
            : $nuevaNovedad;

        $cotizacion->update([
            'estado' => 'Anulada',
            'novedades' => $novedadesActualizadas,
        ]);

        Log::info("Cotización #{$cotizacion->numero_cotizacion} anulada por asesor {$nombreUsuario}", [
            'novedad' => $novedad,
            'fecha' => now(),
        ]);

        return $cotizacion;
    }
}
