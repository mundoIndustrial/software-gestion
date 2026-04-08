<?php

namespace App\Application\Shared\Contracts;

interface AuditRepositoryInterface
{
    public function registrar(
        string $eventType,
        string $description,
        int $userId,
        string $pedido,
        array $metadata = [],
    ): void;
}
