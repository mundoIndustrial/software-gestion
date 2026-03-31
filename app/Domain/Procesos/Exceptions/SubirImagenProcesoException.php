<?php

namespace App\Domain\Procesos\Exceptions;

final class SubirImagenProcesoException extends \DomainException
{
    public static function procesoNoExiste(int $procesoPrendaDetalleId): self
    {
        return new self("El proceso {$procesoPrendaDetalleId} no existe");
    }

    public static function imagenDuplicada(): self
    {
        return new self('Esta imagen ya fue subida anteriormente');
    }
}
