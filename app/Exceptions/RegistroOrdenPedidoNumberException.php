<?php

namespace App\Exceptions;

/**
 * RegistroOrdenPedidoNumberException
 * 
 * Se lanza cuando hay problemas con números de pedido
 * - Número no es el siguiente esperado
 * - Número ya existe
 * - Número inválido
 * 
 * HTTP 422 Unprocessable Entity
 */
class RegistroOrdenPedidoNumberException extends RegistroOrdenException
{
    protected $statusCode = 422;

    public function __construct(
        string $message = 'Error con número de pedido',
        int $expectedPedido = 0,
        int $providedPedido = 0,
        array $context = [],
        Exception $previous = null
    ) {
        $context['expected_pedido'] = $expectedPedido;
        $context['provided_pedido'] = $providedPedido;
        
        parent::__construct(
            $message,
            'PEDIDO_NUMBER_ERROR',
            422,
            $context,
            $previous
        );
    }

    /**
     * Factory para número no esperado
     */
    public static function unexpectedNumber(int $expected, int $provided): self
    {
        return new self(
            "Número de pedido inválido. Esperado: {$expected}, Recibido: {$provided}",
            $expected,
            $provided
        );
    }

    /**
     * Factory para número duplicado
     */
    public static function duplicateNumber(int $pedido): self
    {
        return new self(
            "El número de pedido {$pedido} ya existe",
            0,
            $pedido,
            ['duplicate' => true]
        );
    }
}
