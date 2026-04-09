<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Application\Pedidos\DTOs\PedidoNormalizadorDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Coordina el procesamiento de imagenes del flujo de creacion de pedidos.
 */
class PedidoImageManager
{
    public function __construct(
        private PedidoImagenesService $pedidoImagenesService,
        private MapeoImagenesService $mapeoImagenesService,
    ) {}

    public function procesarCreacionPedido(
        int $pedidoId,
        PedidoNormalizadorDTO $dtoPedido,
        Request $request,
        array $prendas,
        array $epps
    ): void {
        $this->pedidoImagenesService->crearCarpetasPedido($pedidoId);
        $this->mapeoImagenesService->mapearYCrearFotos($dtoPedido, $pedidoId, $request);

        $prendasDB = \App\Models\PedidoProduccion::findOrFail($pedidoId)
            ->prendas()
            ->get();

        foreach ($prendasDB as $indice => $prendaDB) {
            $this->pedidoImagenesService->procesarImagenesPrenda($request, $pedidoId, $indice, $prendaDB);
        }

        if (!empty($epps)) {
            $this->pedidoImagenesService->procesarImagenesDeEpps($request, $pedidoId, $epps);
        }

        if (!empty($prendas)) {
            $this->pedidoImagenesService->procesarImagenesPorTalla($request, $pedidoId, $prendas);
            $this->pedidoImagenesService->procesarImagenesDeColores($request, $pedidoId, $prendas);
        }
    }

    public function procesarGuardadoBorrador(
        int $pedidoId,
        PedidoNormalizadorDTO $dtoPedido,
        Request $request,
        array $epps,
        array $prendas,
        array $nuevasPrendasIds = [],
        array $nuevasPrendas = []
    ): void {
        $this->pedidoImagenesService->crearCarpetasPedido($pedidoId);
        $this->mapeoImagenesService->mapearYCrearFotos($dtoPedido, $pedidoId, $request);

        if (!empty($epps)) {
            $this->pedidoImagenesService->procesarImagenesDeEpps($request, $pedidoId, $epps);
        }

        if (!empty($prendas)) {
            foreach ($prendas as $prendaIndex => $prendaData) {
                $procesos = $prendaData['procesos'] ?? [];
                if (empty($procesos)) {
                    continue;
                }

                $this->pedidoImagenesService->procesarImagenesDeProcesos(
                    $request,
                    $pedidoId,
                    $procesos,
                    (int) $prendaIndex
                );
            }

            // Mantener paridad con flujo de creación:
            // en borrador también debemos procesar imágenes por talla (modo especifico)
            // y colores por talla para no perder archivos en estos subflujos.
            $this->pedidoImagenesService->procesarImagenesPorTalla($request, $pedidoId, $prendas);
            $this->pedidoImagenesService->procesarImagenesDeColores($request, $pedidoId, $prendas);
        }

        if (!empty($nuevasPrendasIds) && !empty($nuevasPrendas)) {
            $this->pedidoImagenesService->procesarImagenesNuevasPrendas(
                $request,
                $nuevasPrendasIds,
                $nuevasPrendas
            );
        }
    }

    public function cleanupPedido(int $pedidoId): void
    {
        $carpetaPedido = "pedidos/{$pedidoId}";

        if (!Storage::disk('public')->exists($carpetaPedido)) {
            return;
        }

        Storage::disk('public')->deleteDirectory($carpetaPedido);

        Log::info('[PedidoImageManager] Carpeta de pedido eliminada', [
            'pedido_id' => $pedidoId,
            'carpeta' => $carpetaPedido,
        ]);
    }
}
