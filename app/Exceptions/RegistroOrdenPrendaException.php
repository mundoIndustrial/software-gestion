<?php

namespace App\Exceptions;

/**
 * RegistroOrdenPrendaException
 * 
 * Se lanza cuando hay errores en operaciones con prendas
 * - Error al parsear descripción
 * - Error al validar prendas
 * - Error en formato de tallas
 * 
 * HTTP 422 Unprocessable Entity
 */
class RegistroOrdenPrendaException extends RegistroOrdenException
{
    protected $statusCode = 422;

    public function __construct(
        string $message = 'Error con operación de prendas',
        string $reason = '',
        int $pedido = 0,
        array $context = [],
        Exception $previous = null
    ) {
        $context['reason'] = $reason;
        $context['pedido'] = $pedido;
        
        parent::__construct(
            $message,
            'PRENDA_ERROR',
            422,
            $context,
            $previous
        );
    }

    /**
     * Factory para error al parsear descripción
     */
    public static function parseDescriptionFailed(string $reason): self
    {
        return new self(
            'Error al parsear descripción de prendas',
            $reason
        );
    }

    /**
     * Factory para error de validación
     */
    public static function validationFailed(string $reason): self
    {
        return new self(
            'Las prendas proporcionadas no son válidas',
            $reason
        );
    }

    /**
     * Factory para error en formato de tallas
     */
    public static function invalidTallasFormat(string $reason): self
    {
        return new self(
            'Formato de tallas inválido',
            $reason
        );
    }
}
