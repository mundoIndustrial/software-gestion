<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

class PedidoItemBuilder
{
    public function __construct(
        private PedidoTipoPrendaService $pedidoTipoPrendaService,
    ) {}

    public function crearBase(PedidoProduccion $pedido, array $itemData): PrendaPedido
    {
        $nombrePrenda = $itemData['nombre_prenda'] ?? 'SIN NOMBRE';
        $this->pedidoTipoPrendaService->asegurarTipo($nombrePrenda);

        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => $nombrePrenda,
            'descripcion' => $itemData['descripcion'] ?? null,
            'de_bodega' => $itemData['de_bodega'] ?? 0,
        ]);

        Log::info('[PedidoItemBuilder] Prenda base creada', [
            'prenda_id' => $prenda->id,
            'pedido_id' => $pedido->id,
            'nombre' => $prenda->nombre_prenda,
        ]);

        return $prenda;
    }
}
