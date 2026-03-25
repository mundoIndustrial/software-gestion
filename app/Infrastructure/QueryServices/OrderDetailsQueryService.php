<?php

namespace App\Infrastructure\QueryServices;

use App\Models\Cliente;
use App\Models\LogoPedido;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderDetailsQueryService
{
    public function logoPedidosTableExists(): bool
    {
        return Schema::hasTable('logo_pedidos');
    }

    public function findLogoPedidoByNumeroPedido(string $pedido): ?LogoPedido
    {
        return LogoPedido::where('numero_pedido', $pedido)->first();
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findPedidoProduccionByNumeroPedidoOrFail(string $pedido): PedidoProduccion
    {
        return PedidoProduccion::with([
            'asesora',
            'cotizacion.tipoCotizacion',
        ])->where('numero_pedido', $pedido)->firstOrFail();
    }

    public function getPrendasConRelaciones(int $pedidoProduccionId)
    {
        return PrendaPedido::where('pedido_produccion_id', $pedidoProduccionId)
            ->with([
                'fotos',
                'tallas',
                'procesos.tipoProceso',
                'procesos.imagenes',
            ])
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Fotos de tela para la prenda (para el modal de detalles).
     * Devuelve rutas en orden asc por orden.
     */
    public function getFotosTelaByPrendaId(int $prendaPedidoId)
    {
        return DB::table('prenda_fotos_tela_pedido')
            ->join('prenda_pedido_colores_telas', 'prenda_fotos_tela_pedido.prenda_pedido_colores_telas_id', '=', 'prenda_pedido_colores_telas.id')
            ->where('prenda_pedido_colores_telas.prenda_pedido_id', $prendaPedidoId)
            ->orderBy('prenda_fotos_tela_pedido.orden', 'asc')
            ->get(['prenda_fotos_tela_pedido.ruta_webp', 'prenda_fotos_tela_pedido.ruta_original']);
    }

    public function findClienteNombreById(int $clienteId, string $fallback = ''): string
    {
        try {
            $cliente = Cliente::find($clienteId);
            return $cliente ? ($cliente->nombre ?? $fallback) : $fallback;
        } catch (\Exception $e) {
            return $fallback;
        }
    }
}

