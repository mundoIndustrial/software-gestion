<?php

namespace App\Domain\SupervisorPedidos\ValueObjects;

class QuotationType
{
    /**
     * Verificar si un tipo de cotización es reflectiva
     */
    public static function isReflective(?string $typeName): bool
    {
        if (!$typeName) {
            return false;
        }

        $normalized = strtolower(trim($typeName));
        return $normalized === 'reflectivo' || $normalized === 'reflective';
    }

    /**
     * Verificar si es un tipo válido
     */
    public static function isValid(string $typeName): bool
    {
        $validTypes = ['reflectivo', 'normal', 'estampado', 'bordado'];
        return in_array(strtolower(trim($typeName)), $validTypes);
    }
}
