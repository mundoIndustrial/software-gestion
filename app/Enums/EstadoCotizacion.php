<?php

namespace App\Enums;

enum EstadoCotizacion: string
{
    case BORRADOR = 'BORRADOR';
    case ENVIADA_CONTADOR = 'ENVIADA_CONTADOR';
    case APROBADA_CONTADOR = 'APROBADA_CONTADOR';
    case EN_CORRECCION = 'EN_CORRECCION';
    case APROBADA_COTIZACIONES = 'APROBADA_COTIZACIONES';
    case CONVERTIDA_PEDIDO = 'CONVERTIDA_PEDIDO';
    case FINALIZADA = 'FINALIZADA';

    /**
     * Obtener el nombre legible del estado
     */
    public function label(): string
    {
        return match($this) {
            self::BORRADOR => 'Borrador',
            self::ENVIADA_CONTADOR => 'Enviada a Contador',
            self::APROBADA_CONTADOR => 'Aprobada por Contador',
            self::EN_CORRECCION => 'En Corrección',
            self::APROBADA_COTIZACIONES => 'Aprobada por Aprobador',
            self::CONVERTIDA_PEDIDO => 'Convertida a Pedido',
            self::FINALIZADA => 'Finalizada',
        };
    }

    /**
     * Obtener el color para mostrar en la UI
     */
    public function color(): string
    {
        return match($this) {
            self::BORRADOR => 'gray',
            self::ENVIADA_CONTADOR => 'blue',
            self::APROBADA_CONTADOR => 'yellow',
            self::EN_CORRECCION => 'orange',
            self::APROBADA_COTIZACIONES => 'green',
            self::CONVERTIDA_PEDIDO => 'purple',
            self::FINALIZADA => 'dark-green',
        };
    }

    /**
     * Obtener el icono para mostrar en la UI
     */
    public function icon(): string
    {
        return match($this) {
            self::BORRADOR => 'document',
            self::ENVIADA_CONTADOR => 'arrow-right',
            self::APROBADA_CONTADOR => 'check-circle',
            self::EN_CORRECCION => 'edit',
            self::APROBADA_COTIZACIONES => 'check-double',
            self::CONVERTIDA_PEDIDO => 'exchange',
            self::FINALIZADA => 'flag-checkered',
        };
    }

    /**
     * Obtener las transiciones permitidas desde este estado
     */
    public function transicionesPermitidas(): array
    {
        return match($this) {
            self::BORRADOR => [self::ENVIADA_CONTADOR->value],
            self::ENVIADA_CONTADOR => [self::APROBADA_CONTADOR->value],
            self::APROBADA_CONTADOR => [self::APROBADA_COTIZACIONES->value, self::EN_CORRECCION->value],
            self::EN_CORRECCION => [self::APROBADA_CONTADOR->value],
            self::APROBADA_COTIZACIONES => [self::CONVERTIDA_PEDIDO->value],
            self::CONVERTIDA_PEDIDO => [self::FINALIZADA->value],
            self::FINALIZADA => [],
        };
    }

    /**
     * Verificar si puede transicionar a otro estado
     */
    public function puedePasar(EstadoCotizacion $siguiente): bool
    {
        return in_array($siguiente->value, $this->transicionesPermitidas());
    }
}
