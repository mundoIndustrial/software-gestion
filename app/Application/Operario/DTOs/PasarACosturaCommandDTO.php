<?php

namespace App\Application\Operario\DTOs;

readonly class PasarACosturaCommandDTO
{
    public function __construct(
        public int $pedidoId,
        public int $numeroRecibo,
        public int $prendaId,
        public string $tipoRecibo,
        public string $encargado,
    ) {}
}
