<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PedidoProduccion;
use App\Models\PedidosProcesosPrendaDetalle;
use Illuminate\Support\Facades\Log;

class PedidoProcesoImageManager
{
    public function __construct(
        private ProcesoImagenService $procesoImagenService,
    ) {}

    public function guardarImagenesGenerales(
        PedidosProcesosPrendaDetalle $proceso,
        PedidoProduccion $pedido,
        array $imagenes
    ): void {
        if (empty($imagenes)) {
            return;
        }

        Log::info('[PedidoProcesoImageManager] Guardando imagenes generales del proceso', [
            'proceso_id' => $proceso->id,
            'pedido_id' => $pedido->id,
            'cantidad_imagenes' => count($imagenes),
        ]);

        $this->procesoImagenService->guardarImagenesProcesos(
            $proceso->id,
            $pedido->id,
            $imagenes
        );
    }
}
