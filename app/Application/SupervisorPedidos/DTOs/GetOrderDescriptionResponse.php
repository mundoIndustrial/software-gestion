<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetOrderDescriptionResponse
{
    private bool $success;
    private string $description;
    private ?string $message;

    public function __construct(bool $success, string $description, ?string $message = null)
    {
        $this->success = $success;
        $this->description = $description;
        $this->message = $message;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'description' => $this->description,
            'message' => $this->message,
        ];
    }
}