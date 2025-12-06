<?php

namespace App\Domain\Bodega\Entities;

final class PrendaBodega
{
    private string $nombre;
    private string $descripcion;
    private array $tallas; // Array de ['talla' => 'M', 'cantidad' => 5]
    private int $cantidadTotal;

    private function __construct(
        string $nombre,
        string $descripcion,
        array $tallas
    ) {
        if (empty(trim($nombre))) {
            throw new \InvalidArgumentException('El nombre de la prenda no puede estar vacío');
        }

        if (empty($tallas)) {
            throw new \InvalidArgumentException('La prenda debe tener al menos una talla');
        }

        $this->nombre = trim($nombre);
        $this->descripcion = trim($descripcion);
        $this->tallas = $tallas;
        $this->cantidadTotal = array_sum(array_map(fn($t) => $t['cantidad'] ?? 0, $tallas));
    }

    public static function crear(
        string $nombre,
        string $descripcion,
        array $tallas
    ): self {
        return new self($nombre, $descripcion, $tallas);
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function tallas(): array
    {
        return $this->tallas;
    }

    public function cantidadTotal(): int
    {
        return $this->cantidadTotal;
    }

    public function agregarTalla(string $talla, int $cantidad): void
    {
        if ($cantidad < 1) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0');
        }

        $this->tallas[] = ['talla' => $talla, 'cantidad' => $cantidad];
        $this->cantidadTotal += $cantidad;
    }

    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'tallas' => $this->tallas,
            'cantidad_total' => $this->cantidadTotal,
        ];
    }
}
