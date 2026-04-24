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

    public static function prendaDuplicadaEnBorrador(int $prendaId): self
    {
        return new self(
            "La prenda {$prendaId} aparece duplicada en el borrador. " .
            'No se puede procesar la misma prenda dos veces en una actualización.'
        );
    }

    public static function prendaNuevaDuplicadaEnBorrador(string $localId): self
    {
        return new self(
            "La prenda nueva {$localId} aparece duplicada en el borrador. " .
            'No se puede procesar la misma prenda nueva dos veces en una actualización.'
        );
    }

    public static function eppDuplicadoEnBorrador(int $pedidoEppId): self
    {
        return new self(
            "El EPP {$pedidoEppId} aparece duplicado en el borrador. " .
            'No se puede procesar el mismo EPP dos veces en una actualización.'
        );
    }

    public static function eppNuevoDuplicadoEnBorrador(int $eppId): self
    {
        return new self(
            "El EPP nuevo {$eppId} aparece duplicado en el borrador. " .
            'No se puede procesar el mismo EPP nuevo dos veces en una actualización.'
        );
    }
}
