<?php

namespace App\Traits;

use App\Application\LogoCotizacion\Services\AgregarTecnicaLogoCotizacionService;
use App\Infrastructure\Repositories\LogoCotizacion\LogoCotizacionTecnicaRepository;

/**
 * LogoCotizacionTrait - Trait que proporciona servicios para cotizaciones de logo
 */
trait LogoCotizacionTrait
{
    protected function getAgregarTecnicaService(): AgregarTecnicaLogoCotizacionService
    {
        return new AgregarTecnicaLogoCotizacionService(
            new LogoCotizacionTecnicaRepository()
        );
    }

    protected function getTiposLogoCotizacion(): array
    {
        return \App\Models\TipoLogoCotizacion::activos()
            ->get()
            ->map(fn($tipo) => [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'codigo' => $tipo->codigo,
                'color' => $tipo->color,
                'icono' => $tipo->icono,
            ])
            ->toArray();
    }

    protected function getLogoCotizacionConTecnicas(int $cotizacionId)
    {
        return \App\Models\LogoCotizacion::with('tecnicas.tipo', 'tecnicas.prendas')
            ->where('cotizacion_id', $cotizacionId)
            ->first();
    }
}
