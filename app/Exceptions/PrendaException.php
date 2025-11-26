<?php

namespace App\Exceptions;

/**
 * PrendaException
 * 
 * Excepción personalizada para errores relacionados con prendas
 */
class PrendaException extends \Exception
{
    /**
     * Código de error para prenda no encontrada
     */
    public const NOT_FOUND = 'PRENDA_NOT_FOUND';

    /**
     * Código de error para tipo de prenda no reconocido
     */
    public const TYPE_NOT_RECOGNIZED = 'PRENDA_TYPE_NOT_RECOGNIZED';

    /**
     * Código de error para variante inválida
     */
    public const INVALID_VARIANT = 'PRENDA_INVALID_VARIANT';

    /**
     * Código de error para datos incompletos
     */
    public const INCOMPLETE_DATA = 'PRENDA_INCOMPLETE_DATA';

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
    public function __construct(string $message, string $code = 'PRENDA_ERROR', array $context = [])
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
