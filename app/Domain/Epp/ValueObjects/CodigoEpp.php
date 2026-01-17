<?php

namespace App\Domain\Epp\ValueObjects;

/**
 * ValueObject para Código de EPP
 * Garantiza que el código sea único y válido
 * 
 * Formato: EPP-XXX-### (ej: EPP-CAB-001)
 */
class CodigoEpp
{
    private string $valor;

    public function __construct(string $codigo)
    {
        if (!$this->esValido($codigo)) {
            throw new \InvalidArgumentException("Código EPP inválido: {$codigo}");
        }

        $this->valor = strtoupper($codigo);
    }

    private function esValido(string $codigo): bool
    {
        // Validar formato básico
        if (empty($codigo) || strlen($codigo) > 50) {
            return false;
        }

        // Validar que sea alfanumérico con guiones
        if (!preg_match('/^[A-Z0-9\-]+$/i', $codigo)) {
            return false;
        }

        return true;
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function equals(CodigoEpp $otro): bool
    {
        return $this->valor === $otro->valor();
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
