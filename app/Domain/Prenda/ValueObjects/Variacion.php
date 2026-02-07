<?php

namespace App\Domain\Prenda\ValueObjects;

class Variacion
{
    private function __construct(
        private int $id,
        private string $talla,
        private string $color
    ) {
        if ($id < 1) {
            throw new \InvalidArgumentException("ID de variación debe ser mayor a 0");
        }

        if (empty(trim($talla))) {
            throw new \InvalidArgumentException("Talla no puede estar vacía");
        }

        if (empty(trim($color))) {
            throw new \InvalidArgumentException("Color no puede estar vacío");
        }
    }

    public static function desde(int $id, string $talla, string $color): self
    {
        return new self($id, trim($talla), trim($color));
    }

    public function id(): int
    {
        return $this->id;
    }

    public function talla(): string
    {
        return $this->talla;
    }

    public function color(): string
    {
        return $this->color;
    }

    public function esIgual(self $otra): bool
    {
        return $this->id === $otra->id &&
               strtolower($this->talla) === strtolower($otra->talla) &&
               strtolower($this->color) === strtolower($otra->color);
    }

    public function descriptorUnico(): string
    {
        return "{$this->talla}-{$this->color}";
    }

    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'talla' => $this->talla,
            'color' => $this->color,
        ];
    }
}
