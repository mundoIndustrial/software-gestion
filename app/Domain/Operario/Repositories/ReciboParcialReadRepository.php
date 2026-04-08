<?php

namespace App\Domain\Operario\Repositories;

use App\Models\ReciboPorPartes;

interface ReciboParcialReadRepository
{
    public function findByIdWithRelationsAndTallas(int $id): ?ReciboPorPartes;
}

