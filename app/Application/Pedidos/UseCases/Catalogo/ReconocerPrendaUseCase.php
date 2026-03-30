<?php

namespace App\Application\Pedidos\UseCases\Catalogo;

use App\Application\Pedidos\Exceptions\ReconocerPrendaException;
use App\Infrastructure\Repositories\CatalogoRepository;
use App\Models\TipoPrenda;

class ReconocerPrendaUseCase
{
    public function __construct(
        private readonly CatalogoRepository $repository
    ) {}

    public function execute(string $nombre): TipoPrenda
    {
        if (empty(trim($nombre))) {
            throw ReconocerPrendaException::nombreRequerido();
        }

        $tipo = $this->repository->reconocerPrendaPorNombre($nombre);

        if (!$tipo) {
            throw ReconocerPrendaException::tipoNoReconocido($nombre);
        }

        return $tipo;
    }
}
