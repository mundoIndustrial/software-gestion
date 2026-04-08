<?php

namespace App\Domain\Pedidos\Repositories;

interface PrendaPedidoTallaReadRepository
{
    /**
     * Obtiene una talla por ID para verificacion y logging.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $tallaId): ?array;
}

