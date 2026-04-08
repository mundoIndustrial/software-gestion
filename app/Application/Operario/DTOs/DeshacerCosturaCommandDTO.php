<?php

namespace App\Application\Operario\DTOs;

readonly class DeshacerCosturaCommandDTO
{
    public function __construct(
        public int $pedidoId,
        public int $prendaId,
        public string $tipoRecibo,
    ) {}
}
