<?php

namespace App\Application\SupervisorPedidos\DTOs;

class MarkNotificationAsReadRequest
{
    public function __construct(
        private int $notificationId
    ) {}

    public function getNotificationId(): int
    {
        return $this->notificationId;
    }
}
