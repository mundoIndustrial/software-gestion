<?php

namespace App\Domain\Bodega\ValueObjects;

/**
 * Value Object para representar áreas de bodega
 * Encapsula la lógica de validación y comportamiento de las áreas
 */
class AreaBodega
{
    private const AREAS_VALIDAS = [
        'Costura' => 'Costura',
        'EPP' => 'EPP',
        'Corte' => 'Corte',
        'Otro' => 'Otro'
    ];

    private string $valor;

    public function __construct(string $area)
    {
        $this->validarArea($area);
        $this->valor = $area;
    }

    private function validarArea(string $area): void
    {
        if (!isset(self::AREAS_VALIDAS[$area])) {
            throw new \InvalidArgumentException("Área de bodega no válida: {$area}");
        }
    }

    public function getValor(): string
    {
        return $this->valor;
    }

    public function esCostura(): bool
    {
        return $this->valor === self::AREAS_VALIDAS['Costura'];
    }

    public function esEPP(): bool
    {
        return $this->valor === self::AREAS_VALIDAS['EPP'];
    }

    public function esCorte(): bool
    {
        return $this->valor === self::AREAS_VALIDAS['Corte'];
    }

    public function esOtro(): bool
    {
        return $this->valor === self::AREAS_VALIDAS['Otro'];
    }

    public function equals(AreaBodega $otra): bool
    {
        return $this->valor === $otra->valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }

    /**
     * Factory method para crear áreas válidas
     */
    public static function costura(): self
    {
        return new self(self::AREAS_VALIDAS['Costura']);
    }

    public static function epp(): self
    {
        return new self(self::AREAS_VALIDAS['EPP']);
    }

    public static function corte(): self
    {
        return new self(self::AREAS_VALIDAS['Corte']);
    }

    public static function otro(): self
    {
        return new self(self::AREAS_VALIDAS['Otro']);
    }

    /**
     * Obtener todas las áreas válidas
     */
    public static function getAreasValidas(): array
    {
        return self::AREAS_VALIDAS;
    }
}
