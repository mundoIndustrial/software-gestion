<?php

namespace App\Constants;

/**
 * Constantes para filtrado de órdenes
 * Centraliza mensajes y configuración de filtrado
 */
class FilterOrdersConstants
{
    // Mensajes de log
    const LOG_PREFIX = "[FILTRAR-ORDENES]";
    const LOG_ERROR_FILTRADO = "Error durante filtrado de órdenes";
    
    // Mensajes de respuesta
    const ERROR_FILTRADO_MESSAGE = "Ocurrió un error durante el filtrado de órdenes";
    
    // Códigos HTTP
    const HTTP_ERROR_CODE = 500;
}
