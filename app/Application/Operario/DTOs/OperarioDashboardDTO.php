<?php

namespace App\Application\Operario\DTOs;

readonly class OperarioDashboardDTO
{
    public function __construct(
        public ObtenerPedidosOperarioDTO $operario,
        public \Illuminate\Support\Collection $prendasConRecibos,
        public \App\Models\User $usuario,
        public string $tab,
        public \Illuminate\Support\Collection $recibosCompletados = new \Illuminate\Support\Collection(),
    ) {}
}
