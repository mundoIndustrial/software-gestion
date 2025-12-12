<?php

namespace App\Domain\Operario\ValueObjects;

/**
 * Value Object: AreaOperario
 * 
 * Áreas donde trabajan los operarios
 */
enum AreaOperario: string
{
    case CORTE = 'Corte';
    case COSTURA = 'Costura';
    case BORDADO = 'Bordado';
    case ESTAMPADO = 'Estampado';
    case REFLECTIVO = 'Reflectivo';
    case LAVANDERIA = 'Lavandería';
    case CONTROL_CALIDAD = 'Control Calidad';

    public function toString(): string
    {
        return $this->value;
    }

    public function esCorte(): bool
    {
        return $this === self::CORTE;
    }

    public function esCostura(): bool
    {
        return $this === self::COSTURA;
    }
}
