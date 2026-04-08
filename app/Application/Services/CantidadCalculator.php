<?php

namespace App\Application\Services;

use App\Repositories\PrendaPedidoTallaRepository;

/**
 * CantidadCalculator
 * 
 * Responsabilidad: Calcular cantidad total de una prenda
 * Soporta dos flujos: talla-color y normal
 */
class CantidadCalculator
{
    public function __construct(
        private PrendaPedidoTallaRepository $prendaTallaRepository
    ) {}

    /**
     * Calcular cantidad total de una prenda
     * 
     * @param array $recibo Datos del recibo
     * @return int Cantidad total
     */
    public function calcular(array $recibo): int
    {
        if (empty($recibo['prenda_id'])) {
            return 0;
        }

        return $this->prendaTallaRepository->calcularCantidadTotalPrenda($recibo['prenda_id']);
    }
}
