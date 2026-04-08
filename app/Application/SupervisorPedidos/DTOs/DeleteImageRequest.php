<?php

namespace App\Application\SupervisorPedidos\DTOs;

class DeleteImageRequest
{
    public function __construct(
        private string $type,
        private int $id
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
