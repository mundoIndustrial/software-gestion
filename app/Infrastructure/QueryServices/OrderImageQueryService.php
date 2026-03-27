<?php

namespace App\Infrastructure\QueryServices;

use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

class OrderImageQueryService
{
    public function findPedidoProduccionByNumero(string $numeroPedido): ?PedidoProduccion
    {
        return PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
    }

    /**
     * @return array<int, mixed>
     */
    public function getCotizacionImagenes(?int $cotizacionId): array
    {
        if (!$cotizacionId) {
            return [];
        }

        $cotizacion = Cotizacion::find($cotizacionId);
        if (!$cotizacion || !$cotizacion->imagenes) {
            return [];
        }

        return is_array($cotizacion->imagenes)
            ? $cotizacion->imagenes
            : (json_decode($cotizacion->imagenes, true) ?? []);
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getPrendasByNumeroPedido(string $numeroPedido)
    {
        return DB::table('prendas_pedido')
            ->where('numero_pedido', $numeroPedido)
            ->orderBy('id', 'asc')
            ->get(['id', 'nombre_prenda']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getFotosPrenda(int $prendaPedidoId)
    {
        return DB::table('prenda_fotos_pedido')
            ->where('prenda_pedido_id', $prendaPedidoId)
            ->orderBy('orden', 'asc')
            ->get(['ruta_webp', 'ruta_original', 'ruta_miniatura', 'orden']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getFotosTela(int $prendaPedidoId)
    {
        return DB::table('prenda_fotos_tela_pedido')
            ->where('prenda_pedido_id', $prendaPedidoId)
            ->orderBy('orden', 'asc')
            ->get(['ruta_webp', 'ruta_original', 'ruta_miniatura', 'orden']);
    }

    /**
     * Devuelve el registro de logo_pedidos buscando por:
     * - numero_pedido con o sin '#'
     * - id (si $pedido es numérico)
     */
    public function findLogoPedidoRowByPedido(string $pedido): ?object
    {
        $pedidoConHash = str_starts_with($pedido, '#') ? $pedido : '#' . $pedido;
        $pedidoSinHash = ltrim($pedido, '#');

        $row = DB::table('logo_pedidos')
            ->where(function ($query) use ($pedidoConHash, $pedidoSinHash, $pedido) {
                $query->where('numero_pedido', $pedidoConHash)
                    ->orWhere('numero_pedido', $pedidoSinHash)
                    ->orWhere('id', $pedido);
            })
            ->first(['id', 'numero_pedido', 'pedido_id', 'cliente', 'asesora', 'forma_de_pago']);

        return $row ?: null;
    }

    public function getPedidoNumeroByPedidoProduccionId(int $pedidoProduccionId): ?string
    {
        $row = DB::table('pedidos_produccion')
            ->where('id', $pedidoProduccionId)
            ->first(['numero_pedido']);

        return $row?->numero_pedido ?: null;
    }


}

