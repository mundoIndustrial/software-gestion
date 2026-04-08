<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use Illuminate\Support\Facades\Log;

class PedidoVarianteBuilder
{
    public function crear(PrendaPedido $prenda, array $variaciones): void
    {
        Log::info('[PedidoVarianteBuilder] Creando variantes', [
            'prenda_id' => $prenda->id,
            'variaciones' => $variaciones,
        ]);

        try {
            PrendaVariantePed::create([
                'prenda_pedido_id' => $prenda->id,
                'tipo_manga_id' => $variaciones['tipo_manga_id'] ?? null,
                'tipo_broche_boton_id' => $variaciones['tipo_broche_boton_id'] ?? null,
                'manga_obs' => $variaciones['obs_manga'] ?? null,
                'broche_boton_obs' => $variaciones['obs_broche'] ?? null,
                'tiene_bolsillos' => $variaciones['tiene_bolsillos'] ?? 0,
                'bolsillos_obs' => $variaciones['obs_bolsillos'] ?? null,
            ]);

            Log::info('[PedidoVarianteBuilder] Variantes creadas exitosamente', [
                'prenda_id' => $prenda->id,
                'tipo_manga_id' => $variaciones['tipo_manga_id'] ?? null,
                'tipo_broche_boton_id' => $variaciones['tipo_broche_boton_id'] ?? null,
                'tiene_bolsillos' => $variaciones['tiene_bolsillos'] ?? 0,
            ]);
        } catch (\Exception $e) {
            Log::error('[PedidoVarianteBuilder] Error creando variantes', [
                'prenda_id' => $prenda->id,
                'error' => $e->getMessage(),
                'variaciones' => $variaciones,
            ]);
        }
    }
}
