<?php

namespace App\Domain\Operario\ValueObjects;

/**
 * Value Object: TipoOperario
 * 
 * Tipos de operarios:
 * - CORTADOR: Encargado del área de corte
 * - COSTURERO: Encargado del área de costura
 */
enum TipoOperario: string
{
    case CORTADOR = 'cortador';
    case COSTURERO = 'costurero';

    public function toString(): string
    {
        return match($this) {
            self::CORTADOR => 'Cortador',
            self::COSTURERO => 'Costurero',
        };
    }

    public function esCorte(): bool
    {
        return $this === self::CORTADOR;
    }

    public function esCostura(): bool
    {
        return $this === self::COSTURERO;
    }
}
