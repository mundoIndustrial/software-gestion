<?php

namespace App\Application\Operario\DTOs;

readonly class CambiarAreaControlCalidadCommandDTO
{
    public function __construct(
        public int $pedidoId,
        public int $numeroRecibo,
        public int $prendaId,
        public string $tipoRecibo,
    ) {}
}
