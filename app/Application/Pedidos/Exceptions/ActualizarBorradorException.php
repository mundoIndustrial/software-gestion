<?php

namespace App\Application\Pedidos\Exceptions;

final class ActualizarBorradorException extends \RuntimeException
{
    public static function prendaNoPerteneceAlPedido(int $prendaId, int $pedidoId): self
    {
        return new self("La prenda {$prendaId} no pertenece al pedido {$pedidoId}");
    }

    public static function objetoNoSerializableEnJson(string $rutaActual): self
    {
        return new self(
            "Objeto no serializable en JSON en ruta: {$rutaActual}. " .
            'Las imagenes deben enviarse por FormData, no por JSON.'
        );
    }
}
