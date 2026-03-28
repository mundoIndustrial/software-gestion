<?php

namespace App\Domain\Pedidos\UseCases;

interface CrearProcesoUseCaseContract
{
    public function ejecutar(array $data): array;
}

