<?php

namespace App\Application\Pedidos\Exceptions;

final class ObtenerDetalleCompletoException extends \DomainException
{
    public static function pedidoNoEncontrado(int $idONumero): self
    {
        return new self("Pedido {$idONumero} no encontrado");
    }

    public static function validacion(string $mensaje): self
    {
        return new self($mensaje);
    }

    public static function inesperado(\Throwable $throwable): self
    {
        return new self(
            'Error al obtener detalle completo del pedido: ' . $throwable->getMessage(),
            0,
            $throwable
        );
    }
}
