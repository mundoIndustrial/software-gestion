<?php

namespace App\Constants;

class ObtenerDetallesOrdenConstants
{
    const LOG_PREFIX = '[OBTENER-DETALLES-ORDEN]';
    
    const LOG_ERROR_OBTENER_DETALLES = self::LOG_PREFIX . ' Error al obtener detalles de orden';
    const ERROR_OBTENER_DETALLES_MESSAGE = 'Error al obtener datos';
    const HTTP_ERROR_CODE_NOT_FOUND = 404;
    const HTTP_ERROR_CODE = 500;
}
