<?php

namespace App\Domain\Pedidos\Despacho\Exceptions;

/**
 * DespachoInvalidoException
 * 
 * Exception de dominio lanzada cuando hay un error en la validación
 * de despacho
 */
class DespachoInvalidoException extends \DomainException
{
    public function __construct(string $message = 'Despacho inválido', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
