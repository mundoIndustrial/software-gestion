<?php

namespace App\Exceptions;

/**
 * CotizacionException
 * 
 * Excepción personalizada para errores relacionados con cotizaciones
 */
class CotizacionException extends \Exception
{
    /**
     * Código de error para cotización no encontrada
     */
    public const NOT_FOUND = 'COTIZACION_NOT_FOUND';

    /**
     * Código de error para autorización denegada
     */
    public const UNAUTHORIZED = 'COTIZACION_UNAUTHORIZED';

    /**
     * Código de error para estado inválido
     */
    public const INVALID_STATE = 'COTIZACION_INVALID_STATE';

    /**
     * Código de error para operación inválida
     */
    public const INVALID_OPERATION = 'COTIZACION_INVALID_OPERATION';

    /**
     * Código de error para datos inválidos
     */
    public const INVALID_DATA = 'COTIZACION_INVALID_DATA';

    /**
     * Código del error
     * 
     * @var string
     */
    protected string $errorCode;

    /**
     * Datos adicionales del error
     * 
     * @var array
     */
    protected array $context = [];

    /**
     * Constructor
     * 
     * @param string $message Mensaje de error
     * @param string $code Código del error (usar constantes de la clase)
     * @param array $context Datos adicionales del error
     */
    public function __construct(string $message, string $code = self::INVALID_OPERATION, array $context = [])
    {
        $this->errorCode = $code;
        $this->context = $context;
        
        parent::__construct($message);
    }

    /**
     * Obtener código del error
     * 
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Obtener contexto del error
     * 
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Convertir a array para respuesta JSON
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'context' => $this->context
        ];
    }
}
