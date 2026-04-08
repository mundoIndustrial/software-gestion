<?php

namespace App\Domain\SupervisorPedidos\Exceptions;

class ReceiptNotFoundException extends \DomainException
{
    public function __construct(string $message = 'Recibo no encontrado')
    {
        parent::__construct($message);
    }
}
