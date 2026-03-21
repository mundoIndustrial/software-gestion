<?php

namespace App\Constants;

class ObtenerOpcionesColumnaConstants
{
    const LOG_PREFIX = '[OBTENER-OPCIONES-COLUMNA]';
    
    // Para inválida
    const LOG_ERROR_COLUMNA_INVALIDA = self::LOG_PREFIX . ' Columna no válida';
    const ERROR_COLUMNA_INVALIDA_MESSAGE = 'Columna no válida';
    const HTTP_ERROR_CODE_INVALIDA = 400;
    
    // Para error general
    const LOG_ERROR_OBTENER_OPCIONES = self::LOG_PREFIX . ' Error al obtener opciones de columna';
    const ERROR_OBTENER_OPCIONES_MESSAGE = 'Error al obtener opciones de columna';
    const HTTP_ERROR_CODE = 500;
}
