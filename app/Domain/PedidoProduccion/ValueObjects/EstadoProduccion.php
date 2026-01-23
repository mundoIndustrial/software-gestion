<?php

namespace App\Domain\PedidoProduccion\ValueObjects;

use InvalidArgumentException;

/**
 * EstadoProduccion
 * 
 * Value Object que encapsula los estados válidos de un pedido de producción
 * Es inmutable y valida que solo se usen estados válidos
 */
class EstadoProduccion
{
    private const ESTADOS_VALIDOS = [
        'pendiente',
        'confirmado',
        'en_produccion',
        'completado',
        'anulado',
    ];

    private string $valor;

    public function __construct(string $valor)
    {
        if (!in_array($valor, self::ESTADOS_VALIDOS)) {
            throw new InvalidArgumentException(
                "Estado inválido: {$valor}. Estados válidos: " . implode(', ', self::ESTADOS_VALIDOS)
            );
        }

        $this->valor = $valor;
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function esIgualA(self $otro): bool
    {
        return $this->valor === $otro->valor();
    }

    public function isPendiente(): bool
    {
        return $this->valor === 'pendiente';
    }

    public function isConfirmado(): bool
    {
        return $this->valor === 'confirmado';
    }

    public function isEnProduccion(): bool
    {
        return $this->valor === 'en_produccion';
    }

    public function isCompletado(): bool
    {
        return $this->valor === 'completado';
    }

    public function isAnulado(): bool
    {
        return $this->valor === 'anulado';
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
