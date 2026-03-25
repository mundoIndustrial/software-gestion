<?php

namespace App\Application\UseCases\Orders;

use App\Models\PedidoProduccion;
use App\Application\Pedidos\Services\PedidoDescriptionService;

/**
 * UseCase: Obtener detalles de una orden
 * 
 * Responsabilidades:
 * - Recuperar datos completos de la orden
 * - Calcular totales
 * - Formatear para presentación
 */
class GetOrderUseCase
{
    /**
     * Ejecutar el caso de uso
     */
    public function execute(int $numeroPedido): array
    {
        $order = PedidoProduccion::with('asesora', 'prendas')
            ->where('numero_pedido', $numeroPedido)
            ->firstOrFail();

        return $this->formatOrderData($order);
    }

    /**
     * Formatear datos de la orden para respuesta
     */
    private function formatOrderData(PedidoProduccion $order): array
    {
        $descriptionService = app(PedidoDescriptionService::class);
        
        $orderData = [
            'id' => $order->id,
            'numero_pedido' => $order->numero_pedido,
            'cliente' => $order->cliente,
            'created_at' => $order->created_at,
            'descripcion_prendas' => $descriptionService->generatePrendasDescription($order),
            'estado' => $order->estado,
            'forma_de_pago' => $order->forma_de_pago ?? '-',
            'area' => $order->area,
            'novedades' => $order->novedades,
            'total_cantidad' => 0,
            'total_entregado' => 0,
            'cantidad' => 0,
            'encargado_orden' => '',
            'asesora' => $order->asesora?->name ?? '',
        ];

        // Calcular totales
        try {
            $totalCantidad = \DB::table('prendas')
                ->where('numero_pedido', $order->numero_pedido)
                ->sum('cantidad');
            $orderData['total_cantidad'] = $totalCantidad ?? 0;
            $orderData['cantidad'] = $totalCantidad ?? 0;
        } catch (\Exception $e) {
            \Log::warning('Error calculando cantidad: ' . $e->getMessage());
        }

        try {
            $totalEntregado = \DB::table('entregas')
                ->where('numero_pedido', $order->numero_pedido)
                ->sum('cantidad_entregada');
            $orderData['total_entregado'] = $totalEntregado ?? 0;
        } catch (\Exception $e) {
            \Log::warning('Error calculando entregas: ' . $e->getMessage());
        }

        return $orderData;
    }
}
