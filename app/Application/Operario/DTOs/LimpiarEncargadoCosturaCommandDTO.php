<?php

namespace App\Application\Operario\DTOs;

readonly class LimpiarEncargadoCosturaCommandDTO
{
    public function __construct(
        public int $pedidoId,
        public int $prendaId,
        public string $tipoRecibo,
    ) {}
}
