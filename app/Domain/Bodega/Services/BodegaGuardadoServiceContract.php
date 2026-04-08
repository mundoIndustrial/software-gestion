<?php

namespace App\Domain\Bodega\Services;

interface BodegaGuardadoServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
