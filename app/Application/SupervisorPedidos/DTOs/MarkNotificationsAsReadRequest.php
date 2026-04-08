<?php

namespace App\Application\SupervisorPedidos\DTOs;

class MarkNotificationsAsReadRequest
{
    public function __construct(
        private int $userId
    ) {}

    public function getUserId(): int
    {
        return $this->userId;
    }
}
