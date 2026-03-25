<?php

namespace App\Application\UseCases\CarteraPedidos;

use App\Infrastructure\Repositories\CarteraPedidosRepository;
use Illuminate\Support\Facades\Log;

class ObtenerPedidosPendientesUseCase
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
            Log::debug('ObtenerPedidosPendientesUseCase ejecutado', $filtros);

            $resultado = $this->repository->obtenerPedidosPendientes(
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
            Log::error('Error en ObtenerPedidosPendientesUseCase: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener pedidos: ' . $e->getMessage()
            ];
        }
    }
}
