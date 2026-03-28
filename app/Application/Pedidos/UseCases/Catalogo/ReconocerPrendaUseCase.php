<?php

namespace App\Application\Pedidos\UseCases\Catalogo;

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
            throw new \Exception('Nombre de prenda requerido', 400);
        }

        $tipo = $this->repository->reconocerPrendaPorNombre($nombre);

        if (!$tipo) {
            throw new \Exception("Tipo de prenda '$nombre' no reconocido", 404);
        }

        return $tipo;
    }
}

