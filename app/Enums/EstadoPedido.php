<?php

namespace App\Enums;

enum EstadoPedido: string
{
    case PENDIENTE_SUPERVISOR = 'PENDIENTE_SUPERVISOR';
    case APROBADO_SUPERVISOR = 'APROBADO_SUPERVISOR';
    case EN_PRODUCCION = 'EN_PRODUCCION';
    case FINALIZADO = 'FINALIZADO';

    /**
     * Obtener el nombre legible del estado
     */
    public function label(): string
    {
        return match($this) {
            self::PENDIENTE_SUPERVISOR => 'Pendiente de Supervisor',
            self::APROBADO_SUPERVISOR => 'Aprobado por Supervisor',
            self::EN_PRODUCCION => 'En Producción',
            self::FINALIZADO => 'Finalizado',
        };
    }

    /**
     * Obtener el color para mostrar en la UI
     */
    public function color(): string
    {
        return match($this) {
            self::PENDIENTE_SUPERVISOR => 'blue',
            self::APROBADO_SUPERVISOR => 'yellow',
            self::EN_PRODUCCION => 'orange',
            self::FINALIZADO => 'green',
        };
    }

    /**
     * Obtener el icono para mostrar en la UI
     */
    public function icon(): string
    {
        return match($this) {
            self::PENDIENTE_SUPERVISOR => 'clock',
            self::APROBADO_SUPERVISOR => 'check-circle',
            self::EN_PRODUCCION => 'industry',
            self::FINALIZADO => 'flag-checkered',
        };
    }

    /**
     * Obtener las transiciones permitidas desde este estado
     */
    public function transicionesPermitidas(): array
    {
        return match($this) {
            self::PENDIENTE_SUPERVISOR => [self::APROBADO_SUPERVISOR->value],
            self::APROBADO_SUPERVISOR => [self::EN_PRODUCCION->value],
            self::EN_PRODUCCION => [self::FINALIZADO->value],
            self::FINALIZADO => [],
        };
    }

    /**
     * Verificar si puede transicionar a otro estado
     */
    public function puedePasar(EstadoPedido $siguiente): bool
    {
        return in_array($siguiente->value, $this->transicionesPermitidas());
    }
}
