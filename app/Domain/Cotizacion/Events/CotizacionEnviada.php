<?php

namespace App\Domain\Cotizacion\Events;

use App\Models\Cotizacion;

/**
 * CotizacionEnviada
 *
 * Domain Event que se dispara cuando una cotización es enviada.
 * Indica que la cotización ha pasado del estado BORRADOR a ENVIADA.
 *
 * Responsabilidad: Notificar a los listeners que una cotización fue enviada
 */
class CotizacionEnviada
{
    /**
     * ID de la cotización
     */
    public int $cotizacionId;

    /**
     * Número de cotización asignado
     */
    public string $numeroCotizacion;

    /**
     * ID del tipo de cotización
     */
    public int $tipoCotizacionId;

    /**
     * ID del asesor
     */
    public int $asesorId;

    /**
     * ID del cliente
     */
    public int $clienteId;

    /**
     * Constructor
     */
    public function __construct(
        int $cotizacionId,
        string $numeroCotizacion,
        int $tipoCotizacionId,
        int $asesorId,
        int $clienteId
    ) {
        $this->cotizacionId = $cotizacionId;
        $this->numeroCotizacion = $numeroCotizacion;
        $this->tipoCotizacionId = $tipoCotizacionId;
        $this->asesorId = $asesorId;
        $this->clienteId = $clienteId;
    }

    /**
     * Factory method desde una cotización
     */
    public static function desde(Cotizacion $cotizacion): self
    {
        return new self(
            $cotizacion->id,
            $cotizacion->numero_cotizacion,
            $cotizacion->tipo_cotizacion_id,
            $cotizacion->asesor_id,
            $cotizacion->cliente_id
        );
    }
}
