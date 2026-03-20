<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetFilterOptionsRequest
{
    private string $field;

    public function __construct(string $field)
    {
        $this->field = $field;
    }

    public function getField(): string
    {
        return $this->field;
    }
}
