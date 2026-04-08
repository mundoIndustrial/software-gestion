<?php

namespace App\Domain\Bodega\Services;

interface BodegaNotificacionServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
