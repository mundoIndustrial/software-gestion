<?php

namespace App\Application\Pedidos\UseCases\Catalogo;

use App\Infrastructure\Repositories\CatalogoRepository;
use Illuminate\Database\Eloquent\Collection;

class ObtenerTipoPrendasUseCase
{
    public function __construct(
        private readonly CatalogoRepository $repository
    ) {}

    public function execute(): Collection
    {
        return $this->repository->obtenerTiposPrendas();
    }
}

