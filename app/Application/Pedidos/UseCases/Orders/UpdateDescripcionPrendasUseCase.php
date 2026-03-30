<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Application\Pedidos\Exceptions\UpdateDescripcionPrendasException;
use App\Application\Shared\Contracts\AuditRepositoryInterface;
use App\Application\Shared\Contracts\OrdenEventDispatcherInterface;
use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Services\RegistroOrdenPrendaService;
use App\Services\RegistroOrdenCacheService;

/**
 * UseCase: Actualizar descripción de prendas de una orden
 *
 * Responsabilidades:
 * - Validar datos de entrada
 * - Parsear y reemplazar prendas según la nueva descripción
 * - Invalidar cache de días calculados
 * - Registrar evento de auditoría
 * - Disparar evento de dominio
 */
class UpdateDescripcionPrendasUseCase
{
    public function __construct(
        private RegistroOrdenPrendaService $prendaService,
        private RegistroOrdenCacheService $cacheService,
        private PedidoProduccionReadRepository $pedidoRepository,
        private AuditRepositoryInterface $auditRepository,
        private TransactionManagerInterface $transactionManager,
        private OrdenEventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UpdateDescripcionPrendasRequest $request): array
    {
        $pedido = $request->pedido;
        $nuevaDescripcion = $request->descripcion;

        $orden = $this->pedidoRepository->findByNumeroPedido($pedido);
        if (!$orden) {
            throw UpdateDescripcionPrendasException::pedidoNoEncontrado($pedido);
        }

        $prendas = null;
        $procesarRegistros = false;

        $this->transactionManager->run(function () use ($pedido, $nuevaDescripcion, $request, &$prendas, &$procesarRegistros) {
            $prendas = $this->prendaService->parseDescripcionToPrendas($nuevaDescripcion);
            $procesarRegistros = $this->prendaService->isValidParsedPrendas($prendas);

            if ($procesarRegistros) {
                $this->prendaService->replacePrendas($pedido, $prendas);
            }

            $this->cacheService->invalidateDaysCache($pedido);

            $this->auditRepository->registrar(
                eventType: 'description_updated',
                description: "descripcion y prendas actualizadas para pedido {$pedido}",
                userId: $request->userId,
                pedido: $pedido,
                metadata: ['prendas_count' => count($prendas)],
            );
        });

        $this->eventDispatcher->ordenActualizada([
            'id' => $orden->pedidoId,
            'numero_pedido' => $orden->numeroPedido,
            'pedido' => $orden->numeroPedido,
            'cliente_id' => $orden->clienteId,
            'asesor_id' => $orden->asesorId,
            'estado' => $orden->estado,
        ], 'updated');

        $mensaje = $this->prendaService->getParsedPrendasMessage($prendas);

        return [
            'success' => true,
            'message' => $mensaje,
            'prendas_procesadas' => count($prendas),
            'registros_regenerados' => $procesarRegistros,
        ];
    }
}

