<?php

namespace App\Application\Pedidos\UseCases\Cartera;

use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Infrastructure\Repositories\CarteraPedidosRepository;
use Illuminate\Support\Facades\Log;

class AprobarPedidoUseCase
{
    public function __construct(
        private CarteraPedidosRepository $repository,
        private TransactionManagerInterface $transactionManager
    ) {}

    /**
     * Ejecutar caso de uso
     */
    public function execute(int $pedidoId): array
    {
        $inicio = microtime(true);

        try {
            Log::info('[CARTERA] AprobarPedidoUseCase iniciado', [
                'pedido_id' => $pedidoId,
                'usuario_id' => auth()->id()
            ]);

            return $this->transactionManager->run(function () use ($pedidoId, &$inicio) {
                $pedido = $this->repository->obtenerPedido($pedidoId);
                $response = [
                    'success' => false,
                    'message' => 'Pedido no encontrado',
                    'pedido' => null,
                    'numero_pedido' => null
                ];

                if (!$pedido) {
                    Log::warning('[CARTERA] Pedido no encontrado', ['pedido_id' => $pedidoId]);
                } elseif ($pedido->estado !== 'pendiente_cartera') {
                    // Validar estado
                    Log::warning('[CARTERA] Pedido no está en estado pendiente de cartera', [
                        'pedido_id' => $pedidoId,
                        'estado_actual' => $pedido->estado
                    ]);
                    $response = [
                        'success' => false,
                        'message' => 'El pedido no está en estado pendiente de cartera. Estado actual: ' . $pedido->estado,
                        'pedido' => null,
                        'numero_pedido' => null
                    ];
                } elseif (!$pedido->numero_pedido) {
                    $response = [
                        'success' => false,
                        'message' => 'El pedido no tiene número de pedido asignado.',
                        'pedido' => null,
                        'numero_pedido' => null
                    ];
                } else {
                    $usuarioId = auth()->check() ? auth()->user()->id : null;

                    // Aprobar pedido
                    $pedidoAprobado = $this->repository->aprobarPedido($pedido, $usuarioId);

                    // Generar consecutivo COSTURA-BODEGA
                    try {
                        $this->repository->generarConsecutivoCosturaBodega($pedidoAprobado);
                    } catch (\Exception $e) {
                        Log::warning('[CARTERA] Error al generar consecutivo (no crítico)', [
                            'pedido_id' => $pedidoId,
                            'error' => $e->getMessage()
                        ]);
                    }

                    Log::info('[CARTERA] Pedido aprobado exitosamente', [
                        'pedido_id' => $pedidoAprobado->id,
                        'numero_pedido' => $pedidoAprobado->numero_pedido,
                        'tiempo' => round((microtime(true) - $inicio) * 1000, 2) . 'ms'
                    ]);

                    $response = [
                        'success' => true,
                        'message' => 'Pedido aprobado correctamente',
                        'pedido' => $pedidoAprobado,
                        'numero_pedido' => $pedidoAprobado->numero_pedido
                    ];
                }

                return $response;
            });
        } catch (\Exception $e) {
            $tiempo = round((microtime(true) - $inicio) * 1000, 2);
            Log::error('[CARTERA] Error crítico en AprobarPedidoUseCase', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'tiempo' => $tiempo . 'ms'
            ]);

            return [
                'success' => false,
                'message' => 'Error al aprobar pedido: ' . $e->getMessage()
            ];
        }
    }
}
