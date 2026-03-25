<?php

namespace App\Application\UseCases\Orders;

use App\Services\RegistroOrdenValidationService;
use App\Services\RegistroOrdenCreationService;
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

        if (!$allowAnyPedido) {
            $nextPedido = $this->creationService->getNextPedidoNumber();
            if ($request->pedido != $nextPedido) {
                throw new \Exception(
                    "Se esperaba pedido {$nextPedido}, pero se recibió {$request->pedido}"
                );
            }
        }

        $pedido = $this->creationService->createOrder($validatedData);

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
