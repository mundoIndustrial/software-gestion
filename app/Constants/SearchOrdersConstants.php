<?php

namespace App\Constants;

/**
 * Constantes para búsqueda de órdenes
 * Centraliza mensajes y configuración de búsqueda
 */
class SearchOrdersConstants
{
    // Mensajes de log
    const LOG_PREFIX = "[BUSCAR-ORDENES]";
    const LOG_ERROR_BUSQUEDA = "Error durante búsqueda de órdenes";
    
    // Mensajes de respuesta
    const ERROR_BUSQUEDA_TITLE = "Error en búsqueda";
    const ERROR_BUSQUEDA_MESSAGE = "Ocurrió un error durante la búsqueda de órdenes";
    
    // Códigos HTTP
    const HTTP_ERROR_CODE = 500;
    
    // Formato de respuesta de error
    const ERROR_RESPONSE_FORMAT = [
        'success' => false,
        'message' => self::ERROR_BUSQUEDA_MESSAGE
    ];
}

