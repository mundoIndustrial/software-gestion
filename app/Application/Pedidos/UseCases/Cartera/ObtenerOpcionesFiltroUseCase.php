<?php

namespace App\Application\Pedidos\UseCases\Cartera;

use App\Infrastructure\Repositories\CarteraPedidosRepository;
use Illuminate\Support\Facades\Log;

class ObtenerOpcionesFiltroUseCase
{
    private CarteraPedidosRepository $repository;

    public function __construct(CarteraPedidosRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Ejecutar caso de uso
     */
    public function execute(): array
    {
        try {
            Log::debug('ObtenerOpcionesFiltroUseCase ejecutado');

            $opciones = $this->repository->obtenerOpcionesFiltro();

            return [
                'success' => true,
                'clientes' => $opciones['clientes'],
                'fechas' => $opciones['fechas']
            ];
        } catch (\Exception $e) {
            Log::error('Error en ObtenerOpcionesFiltroUseCase: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener opciones de filtro'
            ];
        }
    }
}

