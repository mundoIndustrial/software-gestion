<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetFilterOptionsResponse
{
    private array $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function toArray(): array
    {
        return [
            'opciones' => $this->options
        ];
    }
}
