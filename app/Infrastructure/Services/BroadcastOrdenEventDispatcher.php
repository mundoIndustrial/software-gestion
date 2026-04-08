<?php

namespace App\Infrastructure\Services;

use App\Application\Shared\Contracts\OrdenEventDispatcherInterface;
use App\Events\OrdenUpdated;

class BroadcastOrdenEventDispatcher implements OrdenEventDispatcherInterface
{
    public function ordenActualizada(mixed $orden, string $action = 'updated'): void
    {
        broadcast(new OrdenUpdated($orden, $action));
    }
}
