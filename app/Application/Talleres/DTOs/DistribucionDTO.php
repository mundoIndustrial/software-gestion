<?php

namespace App\Application\Talleres\DTOs;

class DistribucionDTO
{
    public string $numeroReciboParte;
    public string $talla;
    public int $cantidad;
    public string $tallerNombre;
    public int $cantidadEntregada;
    public int $porcentaje;
    public string $color;

    public function __construct(
        string $numeroReciboParte,
        string $talla,
        int $cantidad,
        string $tallerNombre,
        int $cantidadEntregada,
        int $porcentaje,
        string $color
    ) {
        $this->numeroReciboParte = $numeroReciboParte;
        $this->talla = $talla;
        $this->cantidad = $cantidad;
        $this->tallerNombre = $tallerNombre;
        $this->cantidadEntregada = $cantidadEntregada;
        $this->porcentaje = $porcentaje;
        $this->color = $color;
    }

    public function toArray(): array
    {
        return [
            'numero_recibo_parte' => $this->numeroReciboParte,
            'talla' => $this->talla,
            'cantidad' => $this->cantidad,
            'taller_nombre' => $this->tallerNombre,
            'cantidad_entregada' => $this->cantidadEntregada,
            'porcentaje' => $this->porcentaje,
            'color' => $this->color
        ];
    }
}
