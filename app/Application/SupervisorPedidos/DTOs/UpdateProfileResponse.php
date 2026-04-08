<?php

namespace App\Application\SupervisorPedidos\DTOs;

class UpdateProfileResponse
{
    private bool $success;
    private string $message;
    private array $user;

    public function __construct(bool $success, string $message, array $user = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->user = $user;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getUser(): array
    {
        return $this->user;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'user' => $this->user
        ];
    }
}
