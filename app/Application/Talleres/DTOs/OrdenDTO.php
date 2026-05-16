<?php

namespace App\Application\Talleres\DTOs;

class OrdenDTO
{
    public int $id;
    public string $numeroRecibo;
    public string $cliente;
    public string $descripcion;
    public int $cantidadTotal;
    public int $cantidadEntregada;
    public int $porcentaje;
    public string $color;
    public bool $esDividido;
    public string $encargadoDisplay;
    public string $distribucion;
    public array $distribucionDetalles;

    public function __construct(
        int $id,
        string $numeroRecibo,
        string $cliente,
        string $descripcion,
        int $cantidadTotal,
        int $cantidadEntregada,
        int $porcentaje,
        string $color,
        bool $esDividido,
        string $encargadoDisplay,
        string $distribucion,
        array $distribucionDetalles = []
    ) {
        $this->id = $id;
        $this->numeroRecibo = $numeroRecibo;
        $this->cliente = $cliente;
        $this->descripcion = $descripcion;
        $this->cantidadTotal = $cantidadTotal;
        $this->cantidadEntregada = $cantidadEntregada;
        $this->porcentaje = $porcentaje;
        $this->color = $color;
        $this->esDividido = $esDividido;
        $this->encargadoDisplay = $encargadoDisplay;
        $this->distribucion = $distribucion;
        $this->distribucionDetalles = $distribucionDetalles;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero_recibo' => $this->numeroRecibo,
            'cliente' => $this->cliente,
            'descripcion' => $this->descripcion,
            'talla' => 'N/A',
            'cantidad_total' => $this->cantidadTotal,
            'cantidad_entregada' => $this->cantidadEntregada,
            'porcentaje' => $this->porcentaje,
            'color' => $this->color,
            'es_dividido' => $this->esDividido,
            'encargado_display' => $this->encargadoDisplay,
            'distribucion_detalles' => $this->distribucionDetalles,
            'distribucion' => $this->distribucion
        ];
    }
}
