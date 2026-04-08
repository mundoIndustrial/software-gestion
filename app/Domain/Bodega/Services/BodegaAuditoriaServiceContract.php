<?php

namespace App\Domain\Bodega\Services;

interface BodegaAuditoriaServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
