<?php

namespace App\Application\InventarioTelas\UseCases;

use App\Domain\InventarioTelas\Repositories\InventarioTelaRepositoryInterface;

class ObtenerHistorialInventarioUseCase
{
    public function __construct(
        private InventarioTelaRepositoryInterface $repository
    ) {}

    public function ejecutar()
    {
        return [
            'historial' => $this->repository->obtenerHistorial(),
            'estadisticas' => $this->repository->obtenerEstadisticas(),
            'telas_mas_movidas' => $this->repository->obtenerTelasMasMovidas(),
            'stock_por_tela' => $this->repository->obtenerStockPorTela(),
            'telas' => $this->repository->obtenerTelasParaFiltros(),
        ];
    }
}
