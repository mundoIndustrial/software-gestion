<?php

namespace App\Domain\Bodega\Services;

interface BodegaRepositoryContract
{
    public function call(string $method, array $arguments = []): mixed;
}
