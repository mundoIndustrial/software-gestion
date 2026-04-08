<?php

namespace App\Application\Asesores\UseCases;

use App\Domain\Cotizacion\Repositories\CotizacionDetalleRepositoryInterface;

class ObtenerDatosCotizacionEditarUseCase
{
    public function __construct(
        private CotizacionDetalleRepositoryInterface $cotizacionDetalleRepository
    ) {}

    public function ejecutar(int $cotizacionId): array
    {
        $datosEpp = $this->cotizacionDetalleRepository->obtenerCotizacionConEpp($cotizacionId);
        $datosPrendas = $this->cotizacionDetalleRepository->obtenerCotizacionConPrendas($cotizacionId);
        
        return [
            'epp_cot' => $datosEpp['eppCot'],
            'items_epp' => $datosEpp['items'],
            'items_prendas' => $datosPrendas['prendas']
        ];
    }
}
