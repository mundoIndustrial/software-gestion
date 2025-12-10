<?php

namespace App\Domain\Cotizacion\Exceptions;

/**
 * CotizacionNoAutorizadaException - Excepción cuando el usuario no tiene permiso
 */
final class CotizacionNoAutorizadaException extends \DomainException
{
    public static function usuarioNoEsPropietario(int $cotizacionId, int $usuarioId): self
    {
        return new self(
            "El usuario {$usuarioId} no es propietario de la cotización {$cotizacionId}"
        );
    }

    public static function noTienePermiso(string $accion): self
    {
        return new self("No tienes permiso para {$accion}");
    }
}
