<?php

namespace App\Domain\SupervisorPedidos\ValueObjects;

final class ReceiptType
{
    private const VALID_TYPES = [
        'COSTURA',
        'BORDADO',
        'ESTAMPADO',
        'LOGO',
    ];

    private string $value;

    public function __construct(string $value)
    {
        $upperValue = strtoupper($value);
        if (!in_array($upperValue, self::VALID_TYPES)) {
            throw new \InvalidArgumentException("Tipo de recibo inválido: {$value}");
        }
        $this->value = $upperValue;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isSewing(): bool
    {
        return $this->value === 'COSTURA';
    }

    public function isEmbroidery(): bool
    {
        return $this->value === 'BORDADO';
    }

    public function isStamping(): bool
    {
        return $this->value === 'ESTAMPADO';
    }

    public function isLogo(): bool
    {
        return $this->value === 'LOGO';
    }

    public function equals(ReceiptType $other): bool
    {
        return $this->value === $other->value();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
