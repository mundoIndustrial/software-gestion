<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Domain\Clientes\Services\ClienteService;
use App\Application\Pedidos\DTOs\PedidoNormalizadorDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\Services\PedidoCreationCoordinator;
use App\Infrastructure\Services\Pedidos\PedidoImageManager;
use App\Infrastructure\Services\Pedidos\PedidoImagenesService;
use Illuminate\Support\Facades\Log;

/**
 * Orquesta el guardado transaccional de borradores de pedidos.
 */
class GuardarBorradorUseCase
{
    public function __construct(
        private ClienteService $clienteService,
        private TransactionManagerInterface $transactionManager,
        private PedidoCreationCoordinator $pedidoCreationCoordinator,
        private PedidoImagenesService $pedidoImagenesService,
        private PedidoImageManager $pedidoImageManager,
        private PedidoRepository $pedidoRepository,
    ) {}

    public function ejecutar(GuardarBorradorInput $input): GuardarBorradorOutput
    {
        $inicioTotal = microtime(true);
        $pedidoId = null;

        try {
            Log::info('[GuardarBorradorUseCase] INICIANDO', [
                'asesor_id' => $input->asesorId,
                'timestamp' => now(),
            ]);

            $this->pedidoImagenesService->validarJsonSinFiles($input->datosFrontend);

            $clienteNombre = trim($input->datosFrontend['cliente'] ?? '');
            $cliente = $this->clienteService->obtenerOCrearCliente($clienteNombre);

            Log::info('[GuardarBorradorUseCase] Cliente obtenido/creado', [
                'cliente_id' => $cliente->id,
                'nombre' => $cliente->nombre,
            ]);

            $dtoPedido = PedidoNormalizadorDTO::fromFrontendJSON(
                $input->datosFrontend,
                $cliente->id
            );

            Log::info('[GuardarBorradorUseCase] Pedido normalizado (DTO)', [
                'cliente_id' => $dtoPedido->cliente_id,
                'prendas' => count($dtoPedido->prendas),
                'epps' => count($dtoPedido->epps),
            ]);

            $pedido = $this->transactionManager->run(function () use ($input, $dtoPedido, &$pedidoId) {
                $datosParaServicio = [
                    'cliente' => $dtoPedido->cliente,
                    'orden_compra' => $input->getOrdenCompra(),
                    'asesora' => $dtoPedido->asesora,
                    'forma_de_pago' => $dtoPedido->forma_de_pago,
                    'dia_de_entrega' => $dtoPedido->dia_de_entrega,
                    'observaciones' => $dtoPedido->observaciones,
                    'cliente_id' => $dtoPedido->cliente_id,
                    'items' => $dtoPedido->prendas,
                    'epps' => $dtoPedido->epps,
                ];

                $pedido = $this->pedidoCreationCoordinator->crearPedidoBorrador(
                    $datosParaServicio,
                    $input->asesorId
                );

                $pedidoId = $pedido->id;
                $prendasRequest = $input->datosFrontend['prendas'] ?? [];
                $nuevasPrendasRequest = $input->datosFrontend['nuevas_prendas'] ?? [];

                $nuevasPrendasIds = $this->crearNuevasPrendas($pedido, $nuevasPrendasRequest);
                $prendasParaImagenes = array_values(array_merge(
                    is_array($prendasRequest) ? $prendasRequest : [],
                    is_array($nuevasPrendasRequest) ? $nuevasPrendasRequest : []
                ));

                $this->pedidoImageManager->procesarGuardadoBorrador(
                    $pedidoId,
                    $dtoPedido,
                    $input->request,
                    $input->datosFrontend['epps'] ?? [],
                    $prendasParaImagenes,
                    $nuevasPrendasIds,
                    $nuevasPrendasRequest
                );

                $cantidadTotalPrendas = $this->pedidoRepository->calcularCantidadTotalPrendas($pedidoId);
                $cantidadTotalEpps = $this->pedidoRepository->calcularCantidadTotalEpps($pedidoId);
                $pedido->update([
                    'cantidad_total' => $cantidadTotalPrendas + $cantidadTotalEpps,
                ]);

                return $pedido;
            });

            $tiempoTotal = round((microtime(true) - $inicioTotal) * 1000, 2);

            Log::info('[GuardarBorradorUseCase] BORRADOR GUARDADO EXITOSAMENTE', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido->numero_pedido ?? 'NULL (Borrador)',
                'estado' => $pedido->estado,
                'tiempo_total_ms' => $tiempoTotal,
            ]);

            return new GuardarBorradorOutput(
                success: true,
                message: 'Borrador guardado exitosamente',
                pedido_id: $pedidoId,
                numero_pedido: $pedido->numero_pedido ?? null,
                estado: $pedido->estado,
                redirect_url: route('asesores.pedidos.show', ['id' => $pedidoId]),
                tiempo_ms: $tiempoTotal,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('[GuardarBorradorUseCase] Errores de validacion', [
                'pedido_id' => $pedidoId,
                'errores' => $e->errors(),
            ]);

            return new GuardarBorradorOutput(
                success: false,
                message: 'Errores de validacion',
            );
        } catch (\Exception $e) {
            Log::error('[GuardarBorradorUseCase] ERROR CRITICO', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            if ($pedidoId) {
                try {
                    $this->pedidoImageManager->cleanupPedido($pedidoId);
                } catch (\Exception $cleanupError) {
                    Log::error('[GuardarBorradorUseCase] Error cleanup', [
                        'error' => $cleanupError->getMessage(),
                    ]);
                }
            }

            return new GuardarBorradorOutput(
                success: false,
                message: 'Error al guardar borrador: ' . $e->getMessage(),
            );
        }
    }

    /**
     * @return int[]
     */
    private function crearNuevasPrendas(object $pedido, array $nuevasPrendas): array
    {
        if (empty($nuevasPrendas)) {
            return [];
        }

        $nuevasPrendasIds = [];

        foreach ($nuevasPrendas as $index => $itemData) {
            $prendaCreada = $this->pedidoCreationCoordinator->agregarItemAPedido($pedido, $itemData, (int) $index);
            $nuevasPrendasIds[] = $prendaCreada->id;
        }

        Log::info('[GuardarBorradorUseCase] Nuevas prendas agregadas al borrador', [
            'pedido_id' => $pedido->id,
            'cantidad' => count($nuevasPrendasIds),
        ]);

        return $nuevasPrendasIds;
    }
}
