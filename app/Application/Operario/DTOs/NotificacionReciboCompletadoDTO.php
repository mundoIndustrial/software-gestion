<?php

namespace App\Application\Operario\DTOs;

/**
 * DTO para notificaciones de recibos completados
 */
class NotificacionReciboCompletadoDTO
{
    public function __construct(
        public int $reciboId,
        public string $consecutivo,
        public int $pedidoId,
        public ?int $prendaId,
        public string $tipoRecibo,
        public string $nombreOperario,
        public string $mensaje,
        public bool $esParcial = false,
        public ?int $pedidoParcialId = null,
        public ?string $consecutivoParcial = null,
        public bool $originalCompletado = false,
    ) {}
}
