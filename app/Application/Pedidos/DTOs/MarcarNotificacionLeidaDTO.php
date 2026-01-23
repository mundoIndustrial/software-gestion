<?php

namespace App\Application\Pedidos\DTOs;

/**
 * MarcarNotificacionLeidaDTO
 * 
 * DTO para marcar notificaciones como leÃ­das
 */
class MarcarNotificacionLeidaDTO
{
    public function __construct(
        public readonly ?string $notificacionId = null,
        public readonly bool $marcarTodos = false
    ) {}

    /**
     * Crear para marcar una notificaciÃ³n especÃ­fica
     */
    public static function fromRequest(string $notificacionId): self
    {
        return new self(
            notificacionId: $notificacionId,
            marcarTodos: false
        );
    }

    /**
     * Crear para marcar todas las notificaciones
     */
    public static function marcarTodos(): self
    {
        return new self(
            notificacionId: null,
            marcarTodos: true
        );
    }
}

