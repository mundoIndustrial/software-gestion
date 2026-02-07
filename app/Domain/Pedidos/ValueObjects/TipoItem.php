<?php

namespace App\Domain\Pedidos\ValueObjects;

/**
 * Value Object: TipoItem
 * 
 * Representa el tipo de item que puede agregarse a un pedido
 * - PRENDA: una prenda de vestir (camisa, pantalón, etc)
 * - EPP: Equipo de Protección Personal
 */
final class TipoItem
{
    public const PRENDA = 'prenda';
    public const EPP = 'epp';

    private string $valor;

    private function __construct(string $valor)
    {
        if (!in_array($valor, [self::PRENDA, self::EPP])) {
            throw new \InvalidArgumentException(
                "Tipo de item inválido: {$valor}. Permitidos: " . implode(', ', [self::PRENDA, self::EPP])
            );
        }

        $this->valor = $valor;
    }

    public static function prenda(): self
    {
        return new self(self::PRENDA);
    }

    public static function epp(): self
    {
        return new self(self::EPP);
    }

    public static function desde(string $valor): self
    {
        return new self($valor);
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function esPrenda(): bool
    {
        return $this->valor === self::PRENDA;
    }

    public function esEpp(): bool
    {
        return $this->valor === self::EPP;
    }

    public function __toString(): string
    {
        return $this->valor;
    }

    public function equals(TipoItem $otro): bool
    {
        return $this->valor === $otro->valor;
    }
}
