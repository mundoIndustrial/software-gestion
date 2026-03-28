<?php

namespace App\Domain\Bodega\Services;

interface BodegaNotaServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
