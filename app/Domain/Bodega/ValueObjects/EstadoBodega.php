<?php

namespace App\Domain\Bodega\ValueObjects;

final class EstadoBodega
{
    public const NO_INICIADO = 'No iniciado';
    public const EN_EJECUCION = 'En Ejecución';
    public const ENTREGADO = 'Entregado';
    public const ANULADA = 'Anulada';

    private string $valor;

    private function __construct(string $valor)
    {
        $estadosValidos = [
            self::NO_INICIADO,
            self::EN_EJECUCION,
            self::ENTREGADO,
            self::ANULADA
        ];

        if (!in_array($valor, $estadosValidos, true)) {
            throw new \InvalidArgumentException("Estado inválido: {$valor}");
        }

        $this->valor = $valor;
    }

    public static function noIniciado(): self
    {
        return new self(self::NO_INICIADO);
    }

    public static function enEjecucion(): self
    {
        return new self(self::EN_EJECUCION);
    }

    public static function entregado(): self
    {
        return new self(self::ENTREGADO);
    }

    public static function anulada(): self
    {
        return new self(self::ANULADA);
    }

    public static function desde(string $valor): self
    {
        return new self($valor);
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function esEntregado(): bool
    {
        return $this->valor === self::ENTREGADO;
    }

    public function esAnulada(): bool
    {
        return $this->valor === self::ANULADA;
    }

    public function puedeTransicionarA(EstadoBodega $nuevoEstado): bool
    {
        // Desde ANULADA no se puede transicionar a ningún lado
        if ($this->valor === self::ANULADA) {
            return false;
        }

        // Transiciones permitidas
        $transiciones = [
            self::NO_INICIADO => [self::EN_EJECUCION, self::ANULADA],
            self::EN_EJECUCION => [self::ENTREGADO, self::ANULADA],
            self::ENTREGADO => [self::ANULADA],
        ];

        return in_array($nuevoEstado->valor, $transiciones[$this->valor] ?? []);
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
