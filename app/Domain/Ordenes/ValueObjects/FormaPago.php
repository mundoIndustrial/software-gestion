<?php

namespace App\Domain\Ordenes\ValueObjects;

/**
 * Value Object: FormaPago
 * 
 * Formas de pago permitidas en el sistema.
 */
final class FormaPago
{
    private const CONTADO = 'Contado';
    private const CREDITO_15 = 'Crédito 15 días';
    private const CREDITO_30 = 'Crédito 30 días';
    private const CREDITO_60 = 'Crédito 60 días';
    private const TRANSFERENCIA = 'Transferencia';
    private const CHEQUE = 'Cheque';

    private readonly string $valor;
    private readonly ?int $diasCredito;

    private function __construct(string $valor)
    {
        if (!in_array($valor, $this->valoresPermitidos())) {
            throw new \InvalidArgumentException("Forma de pago inválida: {$valor}");
        }

        $this->valor = $valor;
        $this->diasCredito = $this->extraerDiasCredito($valor);
    }

    public static function contado(): self
    {
        return new self(self::CONTADO);
    }

    public static function credito15(): self
    {
        return new self(self::CREDITO_15);
    }

    public static function credito30(): self
    {
        return new self(self::CREDITO_30);
    }

    public static function credito60(): self
    {
        return new self(self::CREDITO_60);
    }

    public static function transferencia(): self
    {
        return new self(self::TRANSFERENCIA);
    }

    public static function cheque(): self
    {
        return new self(self::CHEQUE);
    }

    public static function desde(string $valor): self
    {
        return new self($valor);
    }

    public function esContado(): bool
    {
        return $this->valor === self::CONTADO;
    }

    public function esCredito(): bool
    {
        return in_array($this->valor, [self::CREDITO_15, self::CREDITO_30, self::CREDITO_60]);
    }

    public function getDiasCredito(): ?int
    {
        return $this->diasCredito;
    }

    public function toString(): string
    {
        return $this->valor;
    }

    public function equals(self $other): bool
    {
        return $this->valor === $other->valor;
    }

    private function extraerDiasCredito(string $valor): ?int
    {
        if (strpos($valor, '15') !== false) return 15;
        if (strpos($valor, '30') !== false) return 30;
        if (strpos($valor, '60') !== false) return 60;
        return null;
    }

    private function valoresPermitidos(): array
    {
        return [
            self::CONTADO,
            self::CREDITO_15,
            self::CREDITO_30,
            self::CREDITO_60,
            self::TRANSFERENCIA,
            self::CHEQUE,
        ];
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
