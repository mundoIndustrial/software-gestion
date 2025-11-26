<?php

namespace App\Exceptions;

/**
 * PedidoException
 * 
 * Excepción personalizada para errores relacionados con pedidos de producción
 */
class PedidoException extends \Exception
{
    /**
     * Código de error para pedido no encontrado
     */
    public const NOT_FOUND = 'PEDIDO_NOT_FOUND';

    /**
     * Código de error para estado inválido
     */
    public const INVALID_STATE = 'PEDIDO_INVALID_STATE';

    /**
     * Código de error para transacción fallida
     */
    public const TRANSACTION_FAILED = 'PEDIDO_TRANSACTION_FAILED';

    /**
     * Código de error para datos inválidos
     */
    public const INVALID_DATA = 'PEDIDO_INVALID_DATA';

    /**
     * Código de error para prenda no encontrada
     */
    public const PRENDA_NOT_FOUND = 'PEDIDO_PRENDA_NOT_FOUND';

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
    public function __construct(string $message, string $code = 'PEDIDO_ERROR', array $context = [])
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
