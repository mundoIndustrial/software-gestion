<?php

namespace App\Infrastructure\Services;

use App\Application\Shared\Contracts\AuditRepositoryInterface;
use App\Models\News;

class NewsAuditRepository implements AuditRepositoryInterface
{
    public function registrar(
        string $eventType,
        string $description,
        int $userId,
        string $pedido,
        array $metadata = [],
    ): void {
        News::create([
            'event_type' => $eventType,
            'description' => $description,
            'user_id' => $userId,
            'pedido' => $pedido,
            'metadata' => $metadata,
        ]);
    }
}
