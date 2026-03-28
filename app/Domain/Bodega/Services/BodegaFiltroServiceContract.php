<?php

namespace App\Domain\Bodega\Services;

interface BodegaFiltroServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
