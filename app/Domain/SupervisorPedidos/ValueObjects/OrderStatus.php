<?php

namespace App\Domain\SupervisorPedidos\ValueObjects;

final class OrderStatus
{
    private const VALID_STATUSES = [
        'PENDIENTE_SUPERVISOR',
        'PENDIENTE_INSUMOS',
        'pendiente_cartera',
        'Pendiente',
        // Legacy display value kept for backward compatibility on old rows
        'Pendiente Insumos',
        'En Ejecución',
        'No iniciado',
        'DEVUELTO_A_ASESORA',
        'Entregado',
        'Anulada',
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (!in_array($value, self::VALID_STATUSES)) {
            throw new \InvalidArgumentException("Estado de orden inválido: {$value}");
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isPending(): bool
    {
        return in_array($this->value, ['PENDIENTE_SUPERVISOR', 'pendiente_cartera'], true);
    }

    public function isApproved(): bool
    {
        return in_array($this->value, ['Pendiente', 'PENDIENTE_INSUMOS', 'Pendiente Insumos', 'En Ejecución']);
    }

    public function isDelivered(): bool
    {
        return $this->value === 'Entregado';
    }

    public function isCancelled(): bool
    {
        return $this->value === 'Anulada';
    }

    public function isReturned(): bool
    {
        return $this->value === 'DEVUELTO_A_ASESORA';
    }

    public function equals(OrderStatus $other): bool
    {
        return $this->value === $other->value();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
