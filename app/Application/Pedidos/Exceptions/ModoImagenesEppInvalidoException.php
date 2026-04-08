<?php

namespace App\Application\Pedidos\Exceptions;

final class ModoImagenesEppInvalidoException extends \InvalidArgumentException
{
    public static function modoRequerido(int $eppId): self
    {
        return new self(
            "EPP {$eppId}: campo 'modo_imagenes' es requerido y debe ser 'upload' o 'reuse'."
        );
    }

    public static function modoNoSoportado(int $eppId, string $modo): self
    {
        return new self(
            "EPP {$eppId}: modo_imagenes '{$modo}' no es valido. Use 'upload' o 'reuse'."
        );
    }

    public static function uploadSinArchivos(int $eppId): self
    {
        return new self(
            "EPP {$eppId}: modo_imagenes='upload' requiere al menos un archivo en FormData."
        );
    }

    public static function reuseSinImagenes(int $eppId): self
    {
        return new self(
            "EPP {$eppId}: modo_imagenes='reuse' requiere arreglo 'imagenes' con URLs existentes."
        );
    }
}
