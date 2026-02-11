<?php

namespace App\Domain\PedidosLogo\Enums;

final class AreaProcesoLogo
{
    public const CREACION_DE_ORDEN = 'CREACION_DE_ORDEN';
    public const PENDIENTE_DISENO = 'PENDIENTE_DISENO';
    public const DISENO = 'DISENO';
    public const PENDIENTE_CONFIRMAR = 'PENDIENTE_CONFIRMAR';
    public const CORTE_Y_APLIQUE = 'CORTE_Y_APLIQUE';
    public const HACIENDO_MUESTRA = 'HACIENDO_MUESTRA';
    public const ESTAMPANDO = 'ESTAMPANDO';
    public const BORDANDO = 'BORDANDO';
    public const ENTREGADO = 'ENTREGADO';
    public const ANULADO = 'ANULADO';
    public const PENDIENTE = 'PENDIENTE';

    public static function all(): array
    {
        return [
            self::CREACION_DE_ORDEN,
            self::PENDIENTE_DISENO,
            self::DISENO,
            self::PENDIENTE_CONFIRMAR,
            self::CORTE_Y_APLIQUE,
            self::HACIENDO_MUESTRA,
            self::ESTAMPANDO,
            self::BORDANDO,
            self::ENTREGADO,
            self::ANULADO,
            self::PENDIENTE,
        ];
    }
}
