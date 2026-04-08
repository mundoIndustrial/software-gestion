<?php

namespace App\Domain\Pedidos\Services;

interface ColorTelaCatalogServiceContract
{
    public function obtenerOCrearColor(?string $nombreColor): ?int;

    public function obtenerOCrearTela(?string $nombreTela, ?string $referencia = null): ?int;

    public function procesarTela(array $telaData): array;

    public function procesarTelas(array $telas): array;
}
