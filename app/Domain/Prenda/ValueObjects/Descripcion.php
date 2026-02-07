<?php

namespace App\Domain\Prenda\ValueObjects;

class Descripcion
{
    private function __construct(private ?string $valor)
    {
        if ($valor !== null) {
            $valor = trim($valor);
            if (strlen($valor) > 1000) {
                throw new \InvalidArgumentException("Descripción no puede exceder 1000 caracteres");
            }
            $this->valor = $valor === '' ? null : $valor;
        }
    }

    public static function desde(?string $descripcion): self
    {
        return new self($descripcion);
    }

    public static function vacia(): self
    {
        return new self(null);
    }

    public function valor(): ?string
    {
        return $this->valor;
    }

    public function tieneValor(): bool
    {
        return $this->valor !== null && $this->valor !== '';
    }

    public function esIgual(self $otra): bool
    {
        if ($this->valor === null && $otra->valor === null) {
            return true;
        }
        if ($this->valor === null || $otra->valor === null) {
            return false;
        }
        return strtolower($this->valor) === strtolower($otra->valor);
    }

    public function largo(): int
    {
        return $this->valor ? strlen($this->valor) : 0;
    }

    public function __toString(): string
    {
        return $this->valor ?? '';
    }
}
