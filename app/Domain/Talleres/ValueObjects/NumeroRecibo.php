<?php

namespace App\Domain\Talleres\ValueObjects;

class NumeroRecibo
{
    private string $numeroBase;
    private ?string $parte;
    private string $numeroCompleto;

    public function __construct(string $numero)
    {
        $this->numeroCompleto = $numero;
        $partes = explode('.', $numero);
        $this->numeroBase = $partes[0];
        $this->parte = isset($partes[1]) ? $partes[1] : null;
    }

    public function getNumeroBase(): string
    {
        return $this->numeroBase;
    }

    public function getParte(): ?string
    {
        return $this->parte;
    }

    public function getNumeroCompleto(): string
    {
        return $this->numeroCompleto;
    }

    public function esDistribuido(): bool
    {
        return $this->parte !== null;
    }

    public function __toString(): string
    {
        return $this->numeroCompleto;
    }

    public function equals(NumeroRecibo $otro): bool
    {
        return $this->numeroCompleto === $otro->numeroCompleto;
    }
}
