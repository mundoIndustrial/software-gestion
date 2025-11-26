<?php

namespace App\Exceptions;

/**
 * ImagenException
 * 
 * Excepción personalizada para errores relacionados con imágenes
 */
class ImagenException extends \Exception
{
    /**
     * Código de error para archivo no soportado
     */
    public const UNSUPPORTED_FORMAT = 'IMAGEN_UNSUPPORTED_FORMAT';

    /**
     * Código de error para archivo demasiado grande
     */
    public const FILE_TOO_LARGE = 'IMAGEN_FILE_TOO_LARGE';

    /**
     * Código de error para error en conversión
     */
    public const CONVERSION_ERROR = 'IMAGEN_CONVERSION_ERROR';

    /**
     * Código de error para error en almacenamiento
     */
    public const STORAGE_ERROR = 'IMAGEN_STORAGE_ERROR';

    /**
     * Código de error para archivo no encontrado
     */
    public const FILE_NOT_FOUND = 'IMAGEN_FILE_NOT_FOUND';

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
    public function __construct(string $message, string $code = 'IMAGEN_ERROR', array $context = [])
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
