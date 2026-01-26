<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Domain\Pedidos\Aggregates\PedidoProduccionAggregate;
use App\Models\PedidoProduccion;
use Illuminate\Events\Dispatcher;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * CrearProduccionPedidoUseCase
 * 
 * Use Case para crear un nuevo pedido de producción
 * 
 * Responsabilidades:
 * - Validar datos de entrada (delegado a agregado)
 * - Crear agregado de dominio
 * - Persistir en base de datos
 * - Publicar domain events
 * - Retornar resultado
 * 
 * Patrón: Command Handler
 */
class CrearProduccionPedidoUseCase
{
    public function __construct(
        private Dispatcher $eventDispatcher
    ) {}

    /**
     * Ejecutar el use case
     */
    public function ejecutar(CrearProduccionPedidoDTO $dto): PedidoProduccionAggregate
    {
        try {
            // 1. CREAR EN BD PRIMERO para obtener ID
            $pedidoModel = PedidoProduccion::create([
                'numero_pedido' => $dto->numeroPedido,
                'cliente' => $dto->cliente,
                'forma_de_pago' => strtolower(trim($dto->formaDePago ?? 'contado')),
                'asesor_id' => $dto->asesorId,
                'cliente_id' => $dto->clienteId,
                'estado' => $dto->estado ?? 'Pendiente',
                'area' => $dto->area ?? 'creacion de pedido',  // ← CRITICAL: Persist area
                'cantidad_total' => 0,
            ]);

            Log::info('✅ [CrearProduccionPedidoUseCase] Pedido creado en BD', [
                'pedido_id' => $pedidoModel->id,
                'numero_pedido' => $pedidoModel->numero_pedido,
                'area' => $pedidoModel->area,
                'estado' => $pedidoModel->estado,
            ]);

            // 2. CREAR EL AGREGADO con ID ya generado
            $agregado = PedidoProduccionAggregate::crear(
                id: $pedidoModel->id,
                numeroPedido: $pedidoModel->numero_pedido,
                cliente: $pedidoModel->cliente,
                formaPago: $pedidoModel->forma_de_pago,
                asesorId: $pedidoModel->asesor_id,
                estado: $pedidoModel->estado,
                area: $pedidoModel->area,
            );

            // 3. PUBLICAR DOMAIN EVENTS
            foreach ($agregado->getUncommittedEvents() as $evento) {
                $this->eventDispatcher->dispatch($evento);
            }

            return $agregado;

        } catch (Exception $e) {
            Log::error(' [CrearProduccionPedidoUseCase] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception("Error al crear pedido de producción: " . $e->getMessage());
        }
    }
}


