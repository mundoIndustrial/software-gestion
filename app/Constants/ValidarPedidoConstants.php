<?php

namespace App\Constants;

class ValidarPedidoConstants
{
    const LOG_PREFIX = '[VALIDAR-PEDIDO]';
    
    const LOG_ERROR_VALIDACION = self::LOG_PREFIX . ' Error al validar pedido';
    const ERROR_VALIDACION_MESSAGE = 'Error al validar pedido';
    const HTTP_ERROR_CODE = 500;
}
