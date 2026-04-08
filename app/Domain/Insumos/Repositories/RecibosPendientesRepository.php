<?php

namespace App\Domain\Insumos\Repositories;

interface RecibosPendientesRepository
{
    public function cambiarEstadoRecibo(int $reciboId, string $nuevoEstado): array;

    public function contarCosturaPendiente(int $userId): array;

    public function marcarReciboVisto(int $reciboId, int $userId): array;

    public function obtenerResumenRecibosPendientes(int $userId): array;

    public function obtenerRecibosCosturaPendientes(): array;
}

