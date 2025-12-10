<?php

namespace App\Domain\Cotizacion\Events;

use App\Domain\Cotizacion\ValueObjects\Cliente;
use App\Domain\Cotizacion\ValueObjects\CotizacionId;
use DateTimeImmutable;

/**
 * CotizacionAceptada - Domain Event
 *
 * Se dispara cuando un cliente acepta una cotización
 */
final readonly class CotizacionAceptada
{
    public function __construct(
        public CotizacionId $cotizacionId,
        public Cliente $cliente,
        public DateTimeImmutable $ocurridoEn
    ) {
    }

    /**
     * Factory method
     */
    public static function crear(CotizacionId $cotizacionId, Cliente $cliente): self
    {
        return new self($cotizacionId, $cliente, new DateTimeImmutable());
    }
}
