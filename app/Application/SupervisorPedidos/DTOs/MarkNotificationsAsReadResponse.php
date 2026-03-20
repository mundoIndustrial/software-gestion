<?php

namespace App\Application\SupervisorPedidos\DTOs;

class MarkNotificationsAsReadResponse
{
    public function __construct(
        private bool $success,
        private string $message,
        private int $notificationsMarked = 0
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'notificationsMarked' => $this->notificationsMarked
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

    public function getNotificationsMarked(): int
    {
        return $this->notificationsMarked;
    }
}
