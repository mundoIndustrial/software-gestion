<?php

namespace App\Domain\Prenda\ValueObjects;

class PrendaNombre
{
    private function __construct(private string $valor)
    {
        if (empty(trim($valor))) {
            throw new \InvalidArgumentException("Nombre de prenda no puede estar vacío");
        }

        if (strlen(trim($valor)) < 3) {
            throw new \InvalidArgumentException("Nombre de prenda debe tener al menos 3 caracteres");
        }

        if (strlen(trim($valor)) > 255) {
            throw new \InvalidArgumentException("Nombre de prenda no puede exceder 255 caracteres");
        }
    }

    public static function desde(string $nombre): self
    {
        return new self(trim($nombre));
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function esIgual(self $otro): bool
    {
        return strtolower($this->valor) === strtolower($otro->valor);
    }

    public function contiene(string $texto): bool
    {
        return stripos($this->valor, $texto) !== false;
    }

    public function largo(): int
    {
        return strlen($this->valor);
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
