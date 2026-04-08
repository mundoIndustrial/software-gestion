<?php

namespace App\Infrastructure\Repositories\Pedidos;

use Illuminate\Support\Facades\DB;

/**
 * Repository para operaciones con Logo Pedidos.
 *
 * Abstrae la capa de persistencia para que servicios no usen DB::table() directamente.
 */
class LogoPedidoRepository
{
    public function obtenerPorId(int $id): ?object
    {
        return DB::table('logo_pedidos')
            ->where('id', $id)
            ->first();
    }

    public function obtenerPorPedidoId(int $pedidoId): ?object
    {
        return DB::table('logo_pedidos')
            ->where('pedido_id', $pedidoId)
            ->first();
    }

    public function obtenerPorIdOPedidoId(int $pedidoId): ?object
    {
        $logoPedido = $this->obtenerPorId($pedidoId);

        if (!$logoPedido) {
            $logoPedido = $this->obtenerPorPedidoId($pedidoId);
        }

        return $logoPedido;
    }

    public function crear(array $datos): int
    {
        return DB::table('logo_pedidos')->insertGetId($datos);
    }

    public function actualizar(int $id, array $datos): bool
    {
        return DB::table('logo_pedidos')
            ->where('id', $id)
            ->update($datos) > 0;
    }

    public function agregarFoto(int $logoPedidoId, int $logoFotoCotizacionId, int $orden = 0): bool
    {
        DB::table('logo_pedido_fotos')->insertOrIgnore([
            'logo_pedido_id' => $logoPedidoId,
            'logo_foto_cotizacion_id' => $logoFotoCotizacionId,
            'orden' => $orden,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    public function obtenerFotos(int $logoPedidoId): array
    {
        return DB::table('logo_pedido_fotos')
            ->where('logo_pedido_id', $logoPedidoId)
            ->orderBy('orden')
            ->get()
            ->toArray();
    }

    public function existe(int $id): bool
    {
        return DB::table('logo_pedidos')
            ->where('id', $id)
            ->exists();
    }

    public function obtenerCompleto(int $id): ?object
    {
        $logoPedido = $this->obtenerPorId($id);

        if (!$logoPedido) {
            return null;
        }

        $logoPedido->fotos = $this->obtenerFotos($id);

        return $logoPedido;
    }
}
