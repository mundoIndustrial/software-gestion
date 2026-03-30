<?php

namespace App\Application\Pedidos\Exceptions;

final class ReconocerPrendaException extends \RuntimeException
{
    public static function nombreRequerido(): self
    {
        return new self('Nombre de prenda requerido', 400);
    }

    public static function tipoNoReconocido(string $nombre): self
    {
        return new self("Tipo de prenda '{$nombre}' no reconocido", 404);
    }
}
