<?php

namespace App\Domain\Prenda\DomainServices;

use App\Domain\Prenda\Entities\Prenda;
use App\Domain\Prenda\ValueObjects\Origen;

class AplicarOrigenAutomaticoDomainService
{
    /**
     * Aplica origen automático a una prenda basado en su tipo de cotización
     * CORE BUSINESS RULE: Reflectivo/Logo → BODEGA, otros → CONFECCION
     * 
     * Esta es la única fuente de verdad para esta regla
     */
    public function aplicar(Prenda $prenda): void
    {
        $origenCalculado = Origen::segunTipoCotizacion($prenda->tipoCotizacion());

        $prenda->reasignarOrigen($origenCalculado);
    }

    /**
     * Calcula origen sin aplicar (para consultas/validaciones previas)
     */
    public function calcular(Prenda $prenda): Origen
    {
        return Origen::segunTipoCotizacion($prenda->tipoCotizacion());
    }

    /**
     * Verifica si origen actual coincide con el que debería tener
     */
    public function esOrigenesConsistente(Prenda $prenda): bool
    {
        $origenEsperado = $this->calcular($prenda);
        return $prenda->origen()->esIgual($origenEsperado);
    }
}
