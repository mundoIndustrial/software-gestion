<?php

namespace App\Application\UseCases\Pedidos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Models\News;
use App\Domain\Pedidos\DTOs\PedidoNormalizadorDTO;
use App\Domain\Pedidos\Services\PedidoWebService;
use App\Domain\Pedidos\Services\PedidoImagenesService;
use App\Domain\Clientes\Services\ClienteService;
use App\Application\Services\ImageUploadService;
use App\Application\Services\ColorTelaService;
use App\Domain\Pedidos\Services\ResolutorImagenesService;
use App\Domain\Pedidos\Services\MapeoImagenesService;
use App\Domain\Pedidos\Services\ProcesoImagenService;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Events\PedidoCreatedEvent;
use Illuminate\Support\Facades\Event;

/**
 * Orquesta la creación completa de un pedido (100% transaccional).
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

    /** Ejecutar creación transaccional del pedido */
    public function ejecutar(CrearPedidoInput $input): CrearPedidoOutput
    {
        $pedidoId = null;
        $inicioTotal = microtime(true);

        try {
            Log::info('[CREAR-PEDIDO] Iniciando', [
                'archivos' => count($input->request->allFiles()),
            ]);

            // Validar JSON
            $this->pedidoImagenesService->validarJsonSinFiles($input->datosFrontend);

            // Obtener/crear cliente
            $clienteNombre = $input->getClienteNombre();
            $cliente = $this->clienteService->obtenerOCrearCliente($clienteNombre);

            // Normalizar DTO
            $dtoPedido = PedidoNormalizadorDTO::fromFrontendJSON(
                $input->datosFrontend,
                $cliente->id
            );

            DB::beginTransaction();

            // Crear pedido base
            $datosParaServicio = [
                'cliente' => $dtoPedido->cliente,
                'asesora' => $dtoPedido->asesora,
                'forma_de_pago' => $dtoPedido->forma_de_pago,
                'observaciones' => $dtoPedido->observaciones,
                'cliente_id' => $dtoPedido->cliente_id,
                'items' => $dtoPedido->prendas,
                'epps' => $dtoPedido->epps,
            ];

            $pedido = $this->pedidoWebService->crearPedidoCompleto($datosParaServicio, $input->usuarioId);
            $pedidoId = $pedido->id;

            // Crear carpetas e imágenes
            $this->pedidoImagenesService->crearCarpetasPedido($pedidoId);
            $this->mapeoImagenes->mapearYCrearFotos($dtoPedido, $pedidoId, $input->request);

            // Imágenes de EPPs
            $eppsData = $input->getEpps();
            if (!empty($eppsData)) {
                $this->pedidoImagenesService->procesarImagenesDeEpps($input->request, $pedidoId, $eppsData);
            }

            // Imágenes por talla
            $prendas = $input->getPrendas();
            if (!empty($prendas)) {
                $this->pedidoImagenesService->procesarImagenesPorTalla($input->request, $pedidoId, $prendas);
            }

            // Imágenes de colores
            $this->pedidoImagenesService->procesarImagenesDeColores($input->request, $pedidoId, $prendas);

            // Calcular cantidades y commit
            $cantidadTotalPrendas = $this->pedidoRepository->calcularCantidadTotalPrendas($pedidoId);
            $cantidadTotalEpps = $this->pedidoRepository->calcularCantidadTotalEpps($pedidoId);
            $cantidadTotal = $cantidadTotalPrendas + $cantidadTotalEpps;
            $pedido->update(['cantidad_total' => $cantidadTotal]);

            DB::commit();

            // Domain Event
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

            // Notificación
            $this->pedidoRepository->crearNotificacionPedido($pedido, $cliente, $input->usuarioId, $cantidadTotalPrendas, $cantidadTotalEpps);

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
            DB::rollBack();
            Log::error('[CREAR-PEDIDO] Error', ['pedido_id' => $pedidoId, 'error' => $e->getMessage()]);

            // Cleanup: eliminar carpeta si se creó
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
