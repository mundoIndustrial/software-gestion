<?php

namespace App\Domain\ValueObjects;

/**
 * Value Object para el estado de entregas
 * Encapsula los estados válidos y su lógica
 */
class EntregaEstado
{
    public const PENDIENTE_INSUMOS = 'PENDIENTE_INSUMOS';
    public const EN_EJECUCION = 'En Ejecucion';
    public const COMPLETADO = 'COMPLETADO';
    public const DEVUELTO = 'DEVUELTO';

    private string $estado;

    private function __construct(string $estado)
    {
        $this->estado = $estado;
    }

    public static function create(string $estado): self
    {
        if (!self::isValid($estado)) {
            throw new \InvalidArgumentException("Estado inválido: {$estado}");
        }

        return new self($estado);
    }

    public static function isValid(string $estado): bool
    {
        return in_array($estado, [
            self::PENDIENTE_INSUMOS,
            self::EN_EJECUCION,
            self::COMPLETADO,
            self::DEVUELTO
        ]);
    }

    /**
     * Obtener todos los estados válidos
     */
    public static function todos(): array
    {
        return [
            self::PENDIENTE_INSUMOS,
            self::EN_EJECUCION,
            self::COMPLETADO,
            self::DEVUELTO
        ];
    }

    /**
     * Convertir a string
     */
    public function toString(): string
    {
        return $this->estado;
    }

    /**
     * Comparar estados
     */
    public function equals(EntregaEstado $other): bool
    {
        return $this->estado === $other->toString();
    }

    /**
     * Verificar si es estado final
     */
    public function isFinal(): bool
    {
        return in_array($this->estado, [self::COMPLETADO, self::DEVUELTO]);
    }
}
