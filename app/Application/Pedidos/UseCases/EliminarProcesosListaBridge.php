<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\EliminarProcesosListaUseCase as LegacyEliminarProcesosListaUseCase;

class EliminarProcesosListaBridge
{
    public function __construct(
        private LegacyEliminarProcesosListaUseCase $legacyUseCase,
    ) {}

    public function ejecutar(array $procesosIds): void
    {
        $this->legacyUseCase->ejecutar($procesosIds);
    }
}


