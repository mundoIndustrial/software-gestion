<?php

namespace App\Domain\Insumos\Repositories;

interface MaterialesWriteRepository
{
    public function guardarMaterialesDetallados(
        string $numeroPedido,
        array $materiales,
        ?int $prendaId = null,
        ?int $prendaBodegaId = null,
        ?int $numeroRecibo = null,
        ?string $tipoRecibo = null
    ): array;

    public function eliminarMaterialPorNombre(string $numeroPedido, string $nombreMaterial, ?int $prendaId = null): array;

    public function guardarObservaciones(string $numeroPedido, string $nombreMaterial, ?string $observaciones): array;
}
