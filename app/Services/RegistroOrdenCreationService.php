<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\News;
use Illuminate\Support\Facades\DB;

/**
 * RegistroOrdenCreationService
 * 
 * Responsabilidad: Lógica de creación de nuevas órdenes
 * Cumple con SRP: Solo maneja creación, no validación ni persistencia de resultados
 * Cumple con DIP: Inyecta modelos necesarios
 */
class RegistroOrdenCreationService
{
    /**
     * Obtener el próximo número de pedido disponible
     */
    public function getNextPedidoNumber(): int
    {
        $lastPedido = PedidoProduccion::max('numero_pedido');
        return $lastPedido ? $lastPedido + 1 : 1;
    }

    /**
     * Crear nueva orden con sus prendas asociadas
     * 
     * @param array $data Datos validados de la orden
     * @return PedidoProduccion La orden creada
     * @throws \Exception Si falla la creación
     */
    public function createOrder(array $data): PedidoProduccion
    {
        DB::beginTransaction();

        try {
            // Crear pedido en PedidoProduccion
            $estado = $data['estado'] ?? 'Pendiente';
            
            $pedido = PedidoProduccion::create([
                'numero_pedido' => $data['pedido'],
                'cliente' => $data['cliente'],
                'estado' => $estado,
                'forma_de_pago' => $data['forma_pago'] ?? null,
                'fecha_de_creacion_de_orden' => $data['fecha_creacion'],
                'area' => $data['area'] ?? 'Creación Orden',
                'novedades' => null,
            ]);

            // Crear prendas en PrendaPedido
            $this->createPrendas($pedido->numero_pedido, $data['prendas']);

            DB::commit();

            return $pedido;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Crear prendas para una orden
     */
    private function createPrendas(int $numeroPedido, array $prendas): void
    {
        foreach ($prendas as $prendaData) {
            $this->createSinglePrenda($numeroPedido, $prendaData);
        }
    }

    /**
     * Crear una prenda individual
     */
    private function createSinglePrenda(int $numeroPedido, array $prendaData): void
    {
        // Calcular cantidad total de la prenda
        $cantidadPrenda = 0;
        $cantidadesPorTalla = [];
        
        foreach ($prendaData['tallas'] as $talla) {
            $cantidadPrenda += $talla['cantidad'];
            $cantidadesPorTalla[$talla['talla']] = $talla['cantidad'];
        }

        // Crear prenda
        PrendaPedido::create([
            'numero_pedido' => $numeroPedido,
            'nombre_prenda' => $prendaData['prenda'],
            'cantidad' => $cantidadPrenda,
            'descripcion' => $prendaData['descripcion'] ?? '',
            'cantidad_talla' => json_encode($cantidadesPorTalla),
        ]);
    }

    /**
     * Registrar evento de creación de orden
     */
    public function logOrderCreated(int $pedido, string $cliente, string $estado): void
    {
        News::create([
            'event_type' => 'order_created',
            'description' => "Nueva orden registrada: Pedido {$pedido} para cliente {$cliente}",
            'user_id' => auth()->id(),
            'pedido' => $pedido,
            'metadata' => ['cliente' => $cliente, 'estado' => $estado]
        ]);
    }

    /**
     * Broadcast evento de orden creada
     */
    public function broadcastOrderCreated(PedidoProduccion $pedido): void
    {
        broadcast(new \App\Events\OrdenUpdated($pedido, 'created'));
    }
}
