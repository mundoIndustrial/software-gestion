<?php

namespace App\Exceptions;

class ValidarPedidoException extends \Exception
{
    protected $logContext = [];

    public function __construct($message = '', array $context = [])
    {
        parent::__construct($message);
        $this->logContext = $context;
    }

    public function getLogContext(): array
    {
        return $this->logContext;
    }
}
