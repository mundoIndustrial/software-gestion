<?php

namespace App\Domain\LogoCotizacion\ValueObjects;

/**
 * TipoTecnica - Value Object que representa los tipos de técnicas
 * 
 * Los tipos válidos son:
 * - BORDADO
 * - ESTAMPADO
 * - SUBLIMADO
 * - DTF
 */
final class TipoTecnica
{
    private int $id;
    private string $nombre;
    private string $codigo;
    private string $color;
    private string $icono;

    public function __construct(
        int $id,
        string $nombre,
        string $codigo,
        string $color = '#3498db',
        string $icono = 'fa-tools'
    ) {
        if (empty(trim($nombre))) {
            throw new \InvalidArgumentException('El nombre de la técnica no puede estar vacío');
        }
        if (empty(trim($codigo))) {
            throw new \InvalidArgumentException('El código de la técnica no puede estar vacío');
        }

        $this->id = $id;
        $this->nombre = trim($nombre);
        $this->codigo = trim($codigo);
        $this->color = $color;
        $this->icono = $icono;
    }

    public static function bordado(): self
    {
        return new self(1, 'BORDADO', 'BOR', '#e74c3c', 'fa-needle');
    }

    public static function estampado(): self
    {
        return new self(2, 'ESTAMPADO', 'EST', '#3498db', 'fa-stamp');
    }

    public static function sublimado(): self
    {
        return new self(3, 'SUBLIMADO', 'SUB', '#f39c12', 'fa-fire');
    }

    public static function dtf(): self
    {
        return new self(4, 'DTF', 'DTF', '#9b59b6', 'fa-film');
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

    public function color(): string
    {
        return $this->color;
    }

    public function icono(): string
    {
        return $this->icono;
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id() &&
               $this->nombre === $other->nombre() &&
               $this->codigo === $other->codigo();
    }

    public function __toString(): string
    {
        return $this->nombre;
    }
}
