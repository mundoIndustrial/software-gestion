<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\Services\ColorTelaService;
use App\Application\Services\ImageUploadService;
use App\Domain\Clientes\Services\ClienteService;
use App\Domain\Pedidos\DTOs\PedidoNormalizadorDTO;
use App\Domain\Pedidos\Events\PedidoCreatedEvent;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Infrastructure\Services\Pedidos\MapeoImagenesService;
use App\Infrastructure\Services\Pedidos\PedidoImagenesService;
use App\Domain\Pedidos\Services\PedidoWebService;
use App\Infrastructure\Services\Pedidos\ProcesoImagenService;
use App\Infrastructure\Services\Pedidos\ResolutorImagenesService;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Orquesta la creación completa de un pedido (una sola frontera transaccional).
 */
class CrearPedidoCompleteUseCase
{
    public function __construct(
        private ClienteService $clienteService,
        private PedidoImagenesService $pedidoImagenesService,
        private PedidoRepository $pedidoRepository,
        private PedidoWebService $pedidoWebService,
        private ImageUploadService $imageUploadService,
        private ColorTelaService $colorTelaService,
        private ResolutorImagenesService $resolutorImagenes,
        private MapeoImagenesService $mapeoImagenes,
        private ProcesoImagenService $procesoImagenService,
    ) {}

    public function ejecutar(CrearPedidoInput $input): CrearPedidoOutput
    {
        $pedidoId = null;
        $inicioTotal = microtime(true);

        try {
            Log::info('[CREAR-PEDIDO] Iniciando', [
                'archivos' => count($input->request->allFiles()),
            ]);

            // Validar JSON (sin files)
            $this->pedidoImagenesService->validarJsonSinFiles($input->datosFrontend);

            // Obtener/crear cliente
            $clienteNombre = $input->getClienteNombre();
            $cliente = $this->clienteService->obtenerOCrearCliente($clienteNombre);

            // Normalizar DTO
            $dtoPedido = PedidoNormalizadorDTO::fromFrontendJSON(
                $input->datosFrontend,
                $cliente->id
            );

            $esBorrador = false;
            $cantidadTotalPrendas = 0;
            $cantidadTotalEpps = 0;

            // Datos para el servicio de dominio
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

            // UNA sola frontera transaccional: pedido + prendas + epps + cálculo + imágenes (con cleanup si falla)
            $pedido = DB::transaction(function () use (
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
                    $borrador = PedidoProduccion::where('id', $borradorId)
                        ->where('estado', 'Borrador')
                        ->first();

                    if ($borrador) {
                        $pedido = $this->pedidoWebService->convertirBorradorEnPedido($borrador, $datosParaServicio, $input->usuarioId);
                        $esBorrador = true;
                    } else {
                        $pedido = $this->pedidoWebService->crearPedidoCompletoDentroTransaccion($datosParaServicio, $input->usuarioId);
                    }
                } else {
                    $pedido = $this->pedidoWebService->crearPedidoCompletoDentroTransaccion($datosParaServicio, $input->usuarioId);
                }

                $pedidoId = $pedido->id;

                // Solo crear carpetas e imágenes cuando es pedido nuevo (no conversión de borrador)
                if (!$esBorrador) {
                    $this->pedidoImagenesService->crearCarpetasPedido($pedidoId);
                    $this->mapeoImagenes->mapearYCrearFotos($dtoPedido, $pedidoId, $input->request);

                    $eppsData = $input->getEpps();
                    if (!empty($eppsData)) {
                        $this->pedidoImagenesService->procesarImagenesDeEpps($input->request, $pedidoId, $eppsData);
                    }

                    $prendas = $input->getPrendas();
                    if (!empty($prendas)) {
                        $this->pedidoImagenesService->procesarImagenesPorTalla($input->request, $pedidoId, $prendas);
                    }

                    $this->pedidoImagenesService->procesarImagenesDeColores($input->request, $pedidoId, $prendas);
                }

                $cantidadTotalPrendas = $this->pedidoRepository->calcularCantidadTotalPrendas($pedidoId);
                $cantidadTotalEpps = $this->pedidoRepository->calcularCantidadTotalEpps($pedidoId);
                $cantidadTotal = $cantidadTotalPrendas + $cantidadTotalEpps;
                $pedido->update(['cantidad_total' => $cantidadTotal]);

                return $pedido;
            });

            // Domain Event (post-commit)
            Event::dispatch(new PedidoCreatedEvent(
                pedidoId: $pedidoId,
                usuarioId: $input->usuarioId,
                estado: 'pendiente',
                metadata: [
                    'numero_pedido' => $pedido->numero_pedido,
                    'cantidad_prendas' => $cantidadTotalPrendas,
                    'cantidad_epps' => $cantidadTotalEpps,
                ]
            ));

            // Notificación (post-commit)
            $this->pedidoRepository->crearNotificacionPedido(
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
            Log::error('[CREAR-PEDIDO] Error', ['pedido_id' => $pedidoId, 'error' => $e->getMessage()]);

            // Cleanup: eliminar carpeta si se creó (los archivos no están transaccionados)
            if ($pedidoId) {
                try {
                    $carpetaPedido = "pedidos/{$pedidoId}";
                    if (Storage::disk('public')->exists($carpetaPedido)) {
                        Storage::disk('public')->deleteDirectory($carpetaPedido);
                    }
                } catch (\Exception $cleanupError) {
                    Log::error('[CREAR-PEDIDO] Error cleanup', ['error' => $cleanupError->getMessage()]);
                }
            }

            return CrearPedidoOutput::failure('Error: ' . $e->getMessage());
        }
    }
}

