<?php

namespace App\Application\Pedidos\Exceptions;

final class JsonContieneObjetoFileException extends \InvalidArgumentException
{
    public static function enRuta(string $rutaActual): self
    {
        return new self(
            "JSON contiene objeto (posiblemente File) en ruta: {$rutaActual} - "
            . "El frontend NO debe serializar objetos FileList, solo string URLs"
        );
    }
}
