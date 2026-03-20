<?php

namespace App\Domain\SupervisorPedidos\ValueObjects;

final class SupervisorId
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('SupervisorId debe ser positivo');
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

    public function equals(SupervisorId $other): bool
    {
        return $this->value === $other->value();
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
