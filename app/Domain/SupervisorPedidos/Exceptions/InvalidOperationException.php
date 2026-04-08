<?php

namespace App\Domain\SupervisorPedidos\Exceptions;

class InvalidOperationException extends \DomainException
{
    public function __construct(string $message = 'Operación inválida')
    {
        parent::__construct($message);
    }
}
