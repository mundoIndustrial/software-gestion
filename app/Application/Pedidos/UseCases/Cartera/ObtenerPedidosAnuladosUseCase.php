<?php

namespace App\Application\Pedidos\UseCases\Cartera;

use App\Infrastructure\Repositories\CarteraPedidosRepository;
use Illuminate\Support\Facades\Log;

class ObtenerPedidosAnuladosUseCase
{
    private CarteraPedidosRepository $repository;

    public function __construct(CarteraPedidosRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Ejecutar caso de uso
     */
    public function execute(array $filtros): array
    {
        try {
            Log::debug('ObtenerPedidosAnuladosUseCase ejecutado', $filtros);

            $resultado = $this->repository->obtenerPedidosAnulados(
                $filtros['page'] ?? 1,
                $filtros['per_page'] ?? 15,
                $filtros['search'] ?? '',
                $filtros['cliente'] ?? '',
                $filtros['fecha_desde'] ?? '',
                $filtros['fecha_hasta'] ?? '',
                $filtros['sort_by'] ?? 'fecha',
                $filtros['sort_order'] ?? 'desc'
            );

            return [
                'success' => true,
                'data' => $resultado
            ];
        } catch (\Exception $e) {
            Log::error('Error en ObtenerPedidosAnuladosUseCase: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener pedidos anulados: ' . $e->getMessage()
            ];
        }
    }
}

