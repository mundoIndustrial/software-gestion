<?php

namespace App\Domain\SupervisorPedidos\ValueObjects;

final class OrderId
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('OrderId debe ser positivo');
        }
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self((int) $value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(OrderId $other): bool
    {
        return $this->value === $other->value();
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
