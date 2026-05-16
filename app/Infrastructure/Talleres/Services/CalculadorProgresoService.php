<?php

namespace App\Infrastructure\Talleres\Services;

use App\Domain\Talleres\Services\CalculadorProgresoServiceContract;
use App\Domain\Talleres\ValueObjects\ProgressoEntrega;

class CalculadorProgresoService implements CalculadorProgresoServiceContract
{
    public function calcularProgreso(int $cantidadEntregada, int $cantidadTotal): ProgressoEntrega
    {
        return new ProgressoEntrega($cantidadEntregada, $cantidadTotal);
    }

    public function calcularProgresosPorTalla(array $entregas, array $cantidades): array
    {
        $progresos = [];

        foreach ($cantidades as $clave => $cantidadTotal) {
            $cantidadEntregada = $entregas[$clave] ?? 0;
            $progresos[$clave] = $this->calcularProgreso($cantidadEntregada, $cantidadTotal);
        }

        return $progresos;
    }

    public function calcularProgresoDistribucion(array $distribucionDetalles): ProgressoEntrega
    {
        $cantidadTotalDistribuida = 0;
        $cantidadEntregadaDistribuida = 0;

        foreach ($distribucionDetalles as $detalle) {
            $cantidadTotalDistribuida += $detalle['cantidad'] ?? 0;
            $cantidadEntregadaDistribuida += $detalle['cantidad_entregada'] ?? 0;
        }

        return new ProgressoEntrega($cantidadEntregadaDistribuida, $cantidadTotalDistribuida);
    }
}
