<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ToggleNewsVistoRequest
{
    public function __construct(
        private int $newsId,
        private int $userId
    ) {}

    public function getNewsId(): int
    {
        return $this->newsId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
