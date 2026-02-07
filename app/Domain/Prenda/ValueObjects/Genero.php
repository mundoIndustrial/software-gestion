<?php

namespace App\Domain\Prenda\ValueObjects;

class Genero
{
    private const DAMA = 1;
    private const CABALLERO = 2;
    private const UNISEX = 3;

    private function __construct(private int $id)
    {
        if (!in_array($id, [self::DAMA, self::CABALLERO, self::UNISEX])) {
            throw new \InvalidArgumentException("Género inválido: {$id}");
        }
    }

    public static function dama(): self
    {
        return new self(self::DAMA);
    }

    public static function caballero(): self
    {
        return new self(self::CABALLERO);
    }

    public static function unisex(): self
    {
        return new self(self::UNISEX);
    }

    public static function desde(int $id): self
    {
        return new self($id);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return match ($this->id) {
            self::DAMA => 'DAMA',
            self::CABALLERO => 'CABALLERO',
            self::UNISEX => 'UNISEX',
            default => 'DESCONOCIDO'
        };
    }

    public function igual(self $otro): bool
    {
        return $this->id === $otro->id;
    }

    public function __toString(): string
    {
        return $this->nombre();
    }
}
