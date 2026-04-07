<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Application\Pedidos\Exceptions\CrearProduccionPedidoException;
use App\Domain\Pedidos\Aggregates\PedidoProduccionAggregate;
use App\Domain\Pedidos\UseCases\CrearProduccionPedidoUseCaseContract;
use App\Models\PedidoProduccion;
use App\Services\PedidoEppService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Throwable;

class CrearProduccionPedidoUseCase implements CrearProduccionPedidoUseCaseContract
{
    public function __construct(
        private Dispatcher $eventDispatcher,
        private PedidoEppService $pedidoEppService
    ) {}

    public function ejecutar(CrearProduccionPedidoDTO $dto): PedidoProduccionAggregate
    {
        try {
            $pedidoModel = PedidoProduccion::create([
                'numero_pedido' => $dto->numeroPedido,
                'cliente' => $dto->cliente,
                'forma_de_pago' => strtolower(trim($dto->formaDePago ?? 'contado')),
                'asesor_id' => $dto->asesorId,
                'cliente_id' => $dto->clienteId,
                'estado' => $dto->estado ?? 'pendiente_cartera',
                'area' => $dto->area ?? 'creacion de pedido',
                'cantidad_total' => 0,
            ]);

            Log::info(' [CrearProduccionPedidoUseCase] Pedido creado en BD', [
                'pedido_id' => $pedidoModel->id,
                'numero_pedido' => $pedidoModel->numero_pedido,
                'area' => $pedidoModel->area,
                'estado' => $pedidoModel->estado,
            ]);

            if (!empty($dto->epps) && is_array($dto->epps)) {
                $this->procesarEppsDelPayload($pedidoModel, $dto->epps);
            }

            $agregado = PedidoProduccionAggregate::crear(
                id: $pedidoModel->id,
                numeroPedido: $pedidoModel->numero_pedido,
                cliente: $pedidoModel->cliente,
                formaPago: $pedidoModel->forma_de_pago,
                asesorId: $pedidoModel->asesor_id,
                estado: $pedidoModel->estado,
                area: $pedidoModel->area,
            );

            foreach ($agregado->getUncommittedEvents() as $evento) {
                $this->eventDispatcher->dispatch($evento);
            }

            return $agregado;
        } catch (Throwable $e) {
            Log::error(' [CrearProduccionPedidoUseCase] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw CrearProduccionPedidoException::fromThrowable($e);
        }
    }

    private function procesarEppsDelPayload(PedidoProduccion $pedido, array $epps): void
    {
        try {
            $eppsFormateadas = [];
            foreach ($epps as $epp) {
                if (!isset($epp['epp_id']) || empty($epp['epp_id'])) {
                    continue;
                }

                $eppsFormateadas[] = [
                    'epp_id' => (int) $epp['epp_id'],
                    'cantidad' => (int) ($epp['cantidad'] ?? 1),
                    'observaciones' => $epp['observaciones'] ?? null,
                    'imagenes' => [],
                ];
            }

            if (!empty($eppsFormateadas)) {
                $this->pedidoEppService->guardarEppsDelPedido($pedido, $eppsFormateadas);

                Log::info('[CrearProduccionPedidoUseCase] EPPs guardados durante creacion', [
                    'pedido_id' => $pedido->id,
                    'cantidad_epps' => count($eppsFormateadas),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('[CrearProduccionPedidoUseCase] Error procesando EPPs', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}



