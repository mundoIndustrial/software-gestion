<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Domain\Pedidos\Aggregates\PedidoProduccionAggregate;
use App\Models\PedidoProduccion;
use App\Services\PedidoEppService;
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
 * - Procesar EPPs si vienen en el payload
 * - Publicar domain events
 * - Retornar resultado
 * 
 * Patrón: Command Handler
 */
class CrearProduccionPedidoUseCase
{
    public function __construct(
        private Dispatcher $eventDispatcher,
        private PedidoEppService $pedidoEppService
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

            // 2. PROCESAR EPPs SI VIENEN EN EL PAYLOAD
            if (!empty($dto->epps) && is_array($dto->epps)) {
                $this->procesarEppsDelPayload($pedidoModel, $dto->epps);
            }

            // 3. CREAR EL AGREGADO con ID ya generado
            $agregado = PedidoProduccionAggregate::crear(
                id: $pedidoModel->id,
                numeroPedido: $pedidoModel->numero_pedido,
                cliente: $pedidoModel->cliente,
                formaPago: $pedidoModel->forma_de_pago,
                asesorId: $pedidoModel->asesor_id,
                estado: $pedidoModel->estado,
                area: $pedidoModel->area,
            );

            // 4. PUBLICAR DOMAIN EVENTS
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

    /**
     * Procesar EPPs que vienen en el payload durante la creación
     */
    private function procesarEppsDelPayload(PedidoProduccion $pedido, array $epps): void
    {
        try {
            // Transformar array de EPPs del frontend al formato esperado por PedidoEppService
            $eppsFormateadas = [];
            foreach ($epps as $epp) {
                if (!isset($epp['epp_id']) || empty($epp['epp_id'])) {
                    continue; // Saltar EPPs sin ID
                }

                $eppsFormateadas[] = [
                    'epp_id' => (int) $epp['epp_id'],
                    'cantidad' => (int) ($epp['cantidad'] ?? 1),
                    'observaciones' => $epp['observaciones'] ?? null,
                    'imagenes' => [] // Las imágenes se agregan por separado, no en la creación inicial
                ];
            }

            if (!empty($eppsFormateadas)) {
                $this->pedidoEppService->guardarEppsDelPedido($pedido, $eppsFormateadas);

                Log::info('[CrearProduccionPedidoUseCase] EPPs guardados durante creación', [
                    'pedido_id' => $pedido->id,
                    'cantidad_epps' => count($eppsFormateadas),
                ]);
            }
        } catch (Exception $e) {
            Log::warning('[CrearProduccionPedidoUseCase] Error procesando EPPs', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
            // No fallar la creación del pedido si falla el procesamiento de EPPs
        }
    }
}

