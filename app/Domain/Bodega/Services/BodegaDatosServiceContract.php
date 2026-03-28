<?php

namespace App\Domain\Bodega\Services;

interface BodegaDatosServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
