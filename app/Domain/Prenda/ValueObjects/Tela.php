<?php

namespace App\Domain\Prenda\ValueObjects;

class Tela
{
    private function __construct(
        private int $id,
        private string $nombre,
        private string $codigo
    ) {
        if ($id < 1) {
            throw new \InvalidArgumentException("ID de tela debe ser mayor a 0");
        }

        if (empty(trim($nombre))) {
            throw new \InvalidArgumentException("Nombre de tela no puede estar vacío");
        }

        if (empty(trim($codigo))) {
            throw new \InvalidArgumentException("Código de tela no puede estar vacío");
        }
    }

    public static function desde(int $id, string $nombre, string $codigo): self
    {
        return new self($id, trim($nombre), trim($codigo));
    }

    public function id(): int
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function codigo(): string
    {
        return $this->codigo;
    }

    public function esIgual(self $otra): bool
    {
        return $this->id === $otra->id &&
               strtolower($this->nombre) === strtolower($otra->nombre) &&
               strtolower($this->codigo) === strtolower($otra->codigo);
    }

    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
        ];
    }
}
