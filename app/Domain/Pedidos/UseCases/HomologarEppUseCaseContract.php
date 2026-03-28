<?php

namespace App\Domain\Pedidos\UseCases;

interface HomologarEppUseCaseContract
{
    public function ejecutar(
        int $pedidoId,
        int $pedidoEppIdAnterior,
        string $motivo,
        int $cantidadNueva,
        ?string $observacionesNuevas,
        ?int $eppIdNuevo
    ): array;
}

