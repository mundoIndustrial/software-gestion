<?php

namespace App\Constants;

class AreaOptions
{
    /**
     * Lista centralizada de todas las áreas disponibles
     * Se usa en toda la aplicación para validación y dropdowns
     */
    public const AREAS = [
        'Creación de Orden',
        'Control Calidad',
        'Entrega',
        'Despacho',
        'Insumos y Telas',
        'Costura',
        'Corte',
        'Bordado',
        'Estampado',
        'Lavandería',
        'Arreglos'
    ];

    /**
     * Obtener la lista de áreas como string para validación
     * Formato: 'area1,area2,area3'
     */
    public static function getValidationString(): string
    {
        return implode(',', self::AREAS);
    }

    /**
     * Obtener la lista de áreas como array
     */
    public static function getArray(): array
    {
        return self::AREAS;
    }
}
