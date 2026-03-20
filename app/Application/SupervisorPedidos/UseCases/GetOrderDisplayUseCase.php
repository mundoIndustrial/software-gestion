<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsViewResponse;
use App\Domain\SupervisorPedidos\Repositories\OrderRepository;

class GetOrderDisplayUseCase
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {}

    /**
     * Obtener detalles de la orden para mostrar en vista
     */
    public function execute(int $orderId): GetOrderDetailsViewResponse
    {
        // Obtener orden con relaciones
        $order = $this->orderRepository->findByIdWithRelations($orderId);
        
        if (!$order) {
            throw new \DomainException('Orden no encontrada');
        }

        // Procesar datos para la vista
        $orderData = $this->formatOrderForDisplay($order);

        return new GetOrderDetailsViewResponse(
            true,
            'Datos de orden obtenidos',
            $orderData
        );
    }

    /**
     * Formatear datos de la orden para mostrar en vista
     */
    private function formatOrderForDisplay(array $order): array
    {
        return [
            'id' => $order['id'] ?? null,
            'numero_pedido' => $order['numero_pedido'] ?? null,
            'cliente' => $order['cliente'] ?? null,
            'estado' => $order['estado'] ?? null,
            'fecha_entrega' => $order['fecha_entrega'] ?? null,
            'prendas' => $this->formatGarments($order['prendas'] ?? []),
            'procesos' => $this->formatProcesses($order['procesos'] ?? []),
            'full_data' => $order
        ];
    }

    /**
     * Formatear datos de prendas
     */
    private function formatGarments(array $garments): array
    {
        return array_map(function($garment) {
            return [
                'id' => $garment['id'] ?? null,
                'nombre' => $garment['nombre'] ?? $garment['nombre_prenda'] ?? null,
                'cantidad' => $garment['cantidad'] ?? null,
                'tallas' => $garment['tallas'] ?? [],
                'colores' => $garment['colores'] ?? [],
                'procesoActual' => $garment['procesoActual'] ?? null
            ];
        }, $garments);
    }

    /**
     * Formatear datos de procesos
     */
    private function formatProcesses(array $processes): array
    {
        return array_map(function($process) {
            return [
                'id' => $process['id'] ?? null,
                'nombre' => $process['nombre'] ?? null,
                'estado' => $process['estado'] ?? null,
                'fecha_inicio' => $process['fecha_inicio'] ?? null,
                'fecha_fin' => $process['fecha_fin'] ?? null
            ];
        }, $processes);
    }
}
