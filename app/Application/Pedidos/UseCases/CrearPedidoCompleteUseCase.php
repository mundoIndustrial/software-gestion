<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Domain\Clientes\Services\ClienteService;
use App\Application\Pedidos\DTOs\PedidoNormalizadorDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\Services\PedidoCreationCoordinator;
use App\Infrastructure\Services\Pedidos\PedidoImageManager;
use App\Infrastructure\Services\Pedidos\PedidoImagenesService;
use App\Infrastructure\Services\Pedidos\PedidoLifecycleService;
use App\Infrastructure\Services\Pedidos\PedidoPostCommitPublisher;
use Illuminate\Support\Facades\Log;

/**
 * Orquesta la creacion completa de un pedido con una sola frontera transaccional.
 */
class CrearPedidoCompleteUseCase
{
    public function __construct(
        private ClienteService $clienteService,
        private TransactionManagerInterface $transactionManager,
        private PedidoImagenesService $pedidoImagenesService,
        private PedidoRepository $pedidoRepository,
        private PedidoCreationCoordinator $pedidoCreationCoordinator,
        private PedidoImageManager $pedidoImageManager,
        private PedidoLifecycleService $pedidoLifecycleService,
        private PedidoPostCommitPublisher $pedidoPostCommitPublisher,
    ) {}

    public function ejecutar(CrearPedidoInput $input): CrearPedidoOutput
    {
        $pedidoId = null;
        $inicioTotal = microtime(true);

        try {
            Log::info('[CREAR-PEDIDO] Iniciando', [
                'archivos' => count($input->request->allFiles()),
            ]);

            $this->pedidoImagenesService->validarJsonSinFiles($input->datosFrontend);

            $clienteNombre = $input->getClienteNombre();
            $cliente = $this->clienteService->obtenerOCrearCliente($clienteNombre);

            $dtoPedido = PedidoNormalizadorDTO::fromFrontendJSON(
                $input->datosFrontend,
                $cliente->id
            );

            $esBorrador = false;
            $cantidadTotalPrendas = 0;
            $cantidadTotalEpps = 0;

            $datosParaServicio = [
                'cliente' => $dtoPedido->cliente,
                'orden_compra' => $input->getOrdenCompra(),
                'asesora' => $dtoPedido->asesora,
                'forma_de_pago' => $dtoPedido->forma_de_pago,
                'observaciones' => $dtoPedido->observaciones,
                'cliente_id' => $dtoPedido->cliente_id,
                'items' => $dtoPedido->prendas,
                'epps' => $dtoPedido->epps,
            ];

            $pedido = $this->transactionManager->run(function () use (
                $input,
                $dtoPedido,
                $datosParaServicio,
                &$pedidoId,
                &$esBorrador,
                &$cantidadTotalPrendas,
                &$cantidadTotalEpps
            ) {
                $borradorId = $input->getBorradorPedidoId();
                Log::info('[CrearPedidoCompleteUseCase] Borrador check', [
                    'borrador_pedido_id' => $borradorId,
                    'tiene_borrador' => !empty($borradorId),
                ]);

                if ($borradorId) {
                    $borrador = $this->pedidoLifecycleService->obtenerBorradorPorId($borradorId);
                    if ($borrador) {
                        $pedido = $this->pedidoLifecycleService->convertirBorradorEnPedido(
                            $borrador,
                            $datosParaServicio
                        );
                        $esBorrador = true;
                    } else {
                        $pedido = $this->pedidoCreationCoordinator->crearPedidoCompletoDentroTransaccion(
                            $datosParaServicio,
                            $input->usuarioId
                        );
                    }
                } else {
                    $pedido = $this->pedidoCreationCoordinator->crearPedidoCompletoDentroTransaccion(
                        $datosParaServicio,
                        $input->usuarioId
                    );
                }

                $pedidoId = $pedido->id;

                if (!$esBorrador) {
                    $this->pedidoImageManager->procesarCreacionPedido(
                        $pedidoId,
                        $dtoPedido,
                        $input->request,
                        $input->getPrendas(),
                        $input->getEpps()
                    );
                }

                $cantidadTotalPrendas = $this->pedidoRepository->calcularCantidadTotalPrendas($pedidoId);
                $cantidadTotalEpps = $this->pedidoRepository->calcularCantidadTotalEpps($pedidoId);
                $pedido->update([
                    'cantidad_total' => $cantidadTotalPrendas + $cantidadTotalEpps,
                ]);

                return $pedido;
            });

            $this->pedidoPostCommitPublisher->publicarPedidoCreado(
                $pedido,
                $cliente,
                $input->usuarioId,
                $cantidadTotalPrendas,
                $cantidadTotalEpps
            );

            $tiempoTotal = round((microtime(true) - $inicioTotal) * 1000, 2);
            Log::info('[CREAR-PEDIDO] Completado', [
                'pedido_id' => $pedidoId,
                'numero' => $pedido->numero_pedido,
                'tiempo_ms' => $tiempoTotal,
            ]);

            return CrearPedidoOutput::success(
                $pedidoId,
                $pedido->numero_pedido,
                $cliente->id,
                [
                    'prendas' => $cantidadTotalPrendas,
                    'epps' => $cantidadTotalEpps,
                    'tiempo_ms' => $tiempoTotal,
                ]
            );
        } catch (\Exception $e) {
            Log::error('[CREAR-PEDIDO] Error', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);

            if ($pedidoId) {
                try {
                    $this->pedidoImageManager->cleanupPedido($pedidoId);
                } catch (\Exception $cleanupError) {
                    Log::error('[CREAR-PEDIDO] Error cleanup', [
                        'error' => $cleanupError->getMessage(),
                    ]);
                }
            }

            return CrearPedidoOutput::failure('Error: ' . $e->getMessage());
        }
    }
}

