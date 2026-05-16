<?php

namespace App\Domain\Talleres\Services;

use App\Domain\Talleres\ValueObjects\ProgressoEntrega;

interface CalculadorProgresoServiceContract
{
    /**
     * Calcula el progreso total de un recibo
     * 
     * @param int $cantidadEntregada
     * @param int $cantidadTotal
     * @return ProgressoEntrega
     */
    public function calcularProgreso(int $cantidadEntregada, int $cantidadTotal): ProgressoEntrega;

    /**
     * Calcula los progresos por talla de un recibo
     * 
     * @param array $entregas Array con estructura [id|talla => cantidad]
     * @param array $cantidades Array con estructura [id|talla => cantidad]
     * @return array Array de ProgressoEntrega indexado por talla
     */
    public function calcularProgresosPorTalla(array $entregas, array $cantidades): array;

    /**
     * Calcula el progreso total de una distribución
     * 
     * @param array $distribucionDetalles
     * @return ProgressoEntrega
     */
    public function calcularProgresoDistribucion(array $distribucionDetalles): ProgressoEntrega;
}
