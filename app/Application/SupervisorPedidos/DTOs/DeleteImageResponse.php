<?php

namespace App\Application\SupervisorPedidos\DTOs;

class DeleteImageResponse
{
    public function __construct(
        private bool $success,
        private string $message,
        private array $filesDeleted = []
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'filesDeleted' => $this->filesDeleted
        ];
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getFilesDeleted(): array
    {
        return $this->filesDeleted;
    }
}
