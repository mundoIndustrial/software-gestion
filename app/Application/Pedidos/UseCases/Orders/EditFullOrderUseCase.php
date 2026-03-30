<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Application\Shared\Contracts\AuditRepositoryInterface;
use App\Application\Shared\Contracts\OrdenEventDispatcherInterface;
use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Services\RegistroOrdenCacheService;
use App\Services\RegistroOrdenPrendaService;
use App\Services\RegistroOrdenValidationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * UseCase: Actualizar orden completa con sus prendas
 *
 * Responsabilidades:
 * - Validar datos completos
 * - Reemplazar prendas
 * - Invalidar cache
 * - Registrar evento
 */
class EditFullOrderUseCase
{
    public function __construct(
        private RegistroOrdenValidationService $validationService,
        private RegistroOrdenPrendaService $prendaService,
        private RegistroOrdenCacheService $cacheService,
        private PedidoProduccionReadRepository $pedidoRepository,
        private AuditRepositoryInterface $auditRepository,
        private TransactionManagerInterface $transactionManager,
        private OrdenEventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Ejecutar el caso de uso
     */
    public function execute(Request $request, int $pedido): array
    {
        $validatedData = $this->validationService->validateEditFullOrderRequest($request);

        $orden = $this->pedidoRepository->findByNumeroPedido((string) $pedido);
        if (!$orden) {
            throw new ModelNotFoundException("Pedido {$pedido} no encontrado");
        }

        $this->transactionManager->run(function () use ($orden, $pedido, $validatedData) {
            $this->pedidoRepository->actualizarDatosBasicos($orden->pedidoId, [
                'estado' => $validatedData['estado'] ?? 'No iniciado',
                'cliente' => $validatedData['cliente'],
                'created_at' => $validatedData['fecha_creacion'],
                'forma_de_pago' => $validatedData['forma_pago'] ?? null,
            ]);

            $this->prendaService->replacePrendas($pedido, $validatedData['prendas']);
            $this->cacheService->invalidateDaysCache($pedido);

            $this->auditRepository->registrar(
                eventType: 'order_updated',
                description: "Orden editada: Pedido {$pedido} para cliente {$validatedData['cliente']}",
                userId: (int) auth()->id(),
                pedido: (string) $pedido,
                metadata: [
                    'cliente' => $validatedData['cliente'],
                    'total_prendas' => count($validatedData['prendas']),
                ],
            );
        });

        $ordenActualizada = $this->pedidoRepository->obtenerPedidoPorId($orden->pedidoId) ?? [
            'id' => $orden->pedidoId,
            'numero_pedido' => $orden->numeroPedido,
            'cliente' => $validatedData['cliente'],
            'asesor_id' => $orden->asesorId,
            'estado' => $validatedData['estado'] ?? $orden->estado,
            'forma_de_pago' => $validatedData['forma_pago'] ?? null,
        ];

        $ordenActualizada['prendas'] = $this->prendaService->getPrendasArray($pedido);

        $this->eventDispatcher->ordenActualizada($ordenActualizada, 'updated');

        return [
            'success' => true,
            'message' => 'Orden actualizada correctamente',
            'pedido' => $pedido,
            'orden' => $ordenActualizada,
        ];
    }
}
