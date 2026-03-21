<?php

namespace App\Exceptions;

class ObtenerDetallesOrdenException extends \Exception
{
    protected $logContext = [];
    protected $statusCode = 500;

    public function __construct($message = '', array $context = [], int $statusCode = 500)
    {
        parent::__construct($message);
        $this->logContext = $context;
        $this->statusCode = $statusCode;
    }

    public function getLogContext(): array
    {
        return $this->logContext;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
