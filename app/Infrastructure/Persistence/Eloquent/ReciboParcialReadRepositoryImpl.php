<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Operario\Repositories\ReciboParcialReadRepository;
use App\Models\ReciboPorPartes;

class ReciboParcialReadRepositoryImpl implements ReciboParcialReadRepository
{
    public function findByIdWithRelationsAndTallas(int $id): ?ReciboPorPartes
    {
        return ReciboPorPartes::query()
            ->with(['tallas', 'pedido', 'prenda'])
            ->find((int) $id);
    }
}

