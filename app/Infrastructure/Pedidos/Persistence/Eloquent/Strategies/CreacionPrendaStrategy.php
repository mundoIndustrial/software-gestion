<?php

namespace App\Infrastructure\Pedidos\Persistence\Eloquent\Strategies;

use App\Models\PrendaPedido;

/**
 * Contrato para estrategias de creación de prendas acopladas a persistencia.
 */
interface CreacionPrendaStrategy
{
    public function procesar(
        array $prendaData,
        int $pedidoProduccionId,
        array $servicios
    ): PrendaPedido;

    public function validar(array $prendaData): bool;

    public function getNombre(): string;
}
