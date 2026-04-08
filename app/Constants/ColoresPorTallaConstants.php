<?php

namespace App\Constants;

class ColoresPorTallaConstants
{
    /**
     * Mensaje de error cuando no se agregan colores
     */
    public const ERROR_AGREGAR_COLOR_MINIMO = 'Debe agregar al menos un color';

    /**
     * Mensaje de error genérico de validación
     */
    public const ERROR_VALIDACION = 'Error de validación';

    // Mensajes de validación de campos requeridos
    public const ERROR_GENERO_REQUERIDO = 'El género es requerido';
    public const ERROR_GENERO_INVALIDO = 'El género debe ser dama, caballero o unisex';
    public const ERROR_TALLA_REQUERIDA = 'La talla es requerida';
    public const ERROR_TIPO_TALLA_REQUERIDO = 'El tipo de talla es requerido';
    public const ERROR_TIPO_TALLA_INVALIDO = 'El tipo de talla debe ser Letra o Número';
    public const ERROR_TELA_REQUERIDA = 'La tela es requerida';
    public const ERROR_COLOR_REQUERIDO = 'El nombre del color es requerido';
    public const ERROR_CANTIDAD_REQUERIDA = 'La cantidad es requerida';
    public const ERROR_CANTIDAD_MINIMA = 'La cantidad debe ser mayor a 0';
}
