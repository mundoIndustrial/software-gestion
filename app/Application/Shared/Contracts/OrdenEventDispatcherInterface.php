<?php

namespace App\Application\Shared\Contracts;

interface OrdenEventDispatcherInterface
{
    public function ordenActualizada(mixed $orden, string $action = 'updated'): void;
}
