<?php

namespace App\Domain\PedidoProduccion\Services;

use App\Models\PedidoProduccion;
use App\Models\User;

/**
 * Servicio de Dominio para crear pedidos en la base de datos
 */
class PedidoCreationService
{
    public function __construct(
        private PedidoSequenceService $sequenceService,
        private ClienteService $clienteService
    ) {}

    /**
     * Crear pedido en la base de datos
     */
    public function crearPedido(string $nombreCliente, User $asesora, ?string $formaDePago = null): PedidoProduccion
    {
        // Obtener o crear cliente
        $cliente = $this->clienteService->obtenerOCrearCliente($nombreCliente);

        // Generar número de pedido
        $numeroPedido = $this->sequenceService->generarNumeroPedido();

        // Crear el pedido
        return PedidoProduccion::create([
            'numero_pedido' => $numeroPedido,
            'cliente' => $nombreCliente,
            'cliente_id' => $cliente->id,
            'asesor_id' => $asesora->id,
            'forma_de_pago' => $formaDePago,
            'estado' => 'pendiente',
            'fecha_de_creacion_de_orden' => now(),
            'cantidad_total' => 0,
        ]);
    }
}
