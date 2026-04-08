<?php

namespace App\Domain\Operario\Services;

use App\Models\User;
use Illuminate\Support\Collection;

interface OperarioPrendasRecibosReadService
{
    public function obtenerPrendasConRecibos(User $usuario): Collection;

    public function obtenerPrendasConRecibosTodosCostura(): Collection;
}

