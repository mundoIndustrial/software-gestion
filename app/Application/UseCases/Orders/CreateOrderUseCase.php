<?php

namespace App\Application\UseCases\Orders;

use App\Services\RegistroOrdenValidationService;
use App\Services\RegistroOrdenCreationService;
use App\Exceptions\RegistroOrdenPedidoNumberException;
use Illuminate\Http\Request;

/**
 * UseCase: Crear una nueva orden
 * 
 * Responsabilidades:
 * - Validar datos de entrada
 * - Verificar número consecutivo
 * - Delegar creación al servicio
 * - Disparar eventos de dominio
 */
class CreateOrderUseCase
{
    public function __construct(
        private RegistroOrdenValidationService $validationService,
        private RegistroOrdenCreationService $creationService,
    ) {}

    public function execute(Request $request, bool $allowAnyPedido = false): array
    {
        $validatedData = $this->validationService->validateStoreRequest($request);

        // Validación del consecutivo se hace dentro de la transacción (fuente de verdad)
        $allowAny = $allowAnyPedido || (bool) ($validatedData['allow_any_pedido'] ?? false);
        $pedido = $this->creationService->createOrder($validatedData, $allowAny);

        $this->creationService->logOrderCreated(
            $pedido->numero_pedido,
            $validatedData['cliente'],
            $validatedData['estado'] ?? 'Pendiente'
        );

        $this->creationService->broadcastOrderCreated($pedido);

        return [
            'success' => true,
            'message' => 'Orden registrada correctamente',
            'pedido' => $pedido->numero_pedido
        ];
    }
}
