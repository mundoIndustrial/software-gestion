<?php

namespace App\Domain\Pedidos\ValueObjects;

/**
 * Value Object: Estado
 * 
 * Estados vÃ¡lidos de un pedido
 * Define transiciones permitidas
 */
class Estado
{
    public const PENDIENTE = 'PENDIENTE';
    public const CONFIRMADO = 'CONFIRMADO';
    public const EN_PRODUCCION = 'EN_PRODUCCION';
    public const COMPLETADO = 'COMPLETADO';
    public const CANCELADO = 'CANCELADO';

    private string $valor;

    private static array $estadosValidos = [
        self::PENDIENTE,
        self::CONFIRMADO,
        self::EN_PRODUCCION,
        self::COMPLETADO,
        self::CANCELADO,
    ];

    private static array $transicionesPermitidas = [
        self::PENDIENTE => [self::CONFIRMADO, self::CANCELADO],
        self::CONFIRMADO => [self::EN_PRODUCCION, self::CANCELADO],
        self::EN_PRODUCCION => [self::COMPLETADO],
        self::COMPLETADO => [],
        self::CANCELADO => [],
    ];

    private function __construct(string $valor)
    {
        $this->validar($valor);
        $this->valor = $valor;
    }

    public static function desde(string $valor): self
    {
        return new self($valor);
    }

    public static function inicial(): self
    {
        return new self(self::PENDIENTE);
    }

    private function validar(string $valor): void
    {
        if (!in_array($valor, self::$estadosValidos)) {
            throw new \InvalidArgumentException(
                "Estado '$valor' invÃ¡lido. VÃ¡lidos: " . implode(', ', self::$estadosValidos)
            );
        }
    }

    public function puedeSeguirA(Estado $nuevoEstado): bool
    {
        $transicionesPermitidas = self::$transicionesPermitidas[$this->valor] ?? [];
        return in_array($nuevoEstado->valor, $transicionesPermitidas);
    }

    public function transicionarA(Estado $nuevoEstado): void
    {
        if (!$this->puedeSeguirA($nuevoEstado)) {
            throw new \DomainException(
                "No se puede pasar de {$this->valor} a {$nuevoEstado->valor}"
            );
        }
    }

    public function esFinal(): bool
    {
        return $this->valor === self::COMPLETADO || $this->valor === self::CANCELADO;
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function esIgualA(Estado $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}

