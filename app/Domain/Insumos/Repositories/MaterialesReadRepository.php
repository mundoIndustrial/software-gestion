<?php

namespace App\Domain\Insumos\Repositories;

interface MaterialesReadRepository
{
    public function obtenerMaterialesPedido(string $numeroPedido, ?int $prendaId = null): array;

    public function marcarTodasNotificacionesLeidas(int $userId): array;

    public function obtenerPrendasPedido(string $numeroPedido): array;

    public function obtenerReciboPrenda(string $numeroPedido, int $prendaId): array;

    public function obtenerOpcionesFiltro(string $column, string $tipoRecibo = 'COSTURA'): array;
}

