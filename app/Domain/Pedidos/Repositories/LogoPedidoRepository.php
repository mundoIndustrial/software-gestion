<?php

namespace App\Domain\Pedidos\Repositories;

use App\Models\LogoPedido;
use Illuminate\Support\Facades\DB;

/**
 * Repository para operaciones con Logo Pedidos
 * 
 * Abstrae la capa de persistencia para que servicios no usen DB::table() directamente
 * Implementa operaciones CRUD especÃ­ficas para LogoPedido
 */
class LogoPedidoRepository
{
    /**
     * Obtener logo_pedido por ID primaria
     * 
     * @param int $id ID del logo_pedido
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        return DB::table('logo_pedidos')
            ->where('id', $id)
            ->first();
    }

    /**
     * Obtener logo_pedido por pedido_id (relación FK)
     * 
     * @param int $pedidoId ID del pedido_produccion
     * @return array|null
     */
    public function obtenerPorPedidoId(int $pedidoId): ?array
    {
        return DB::table('logo_pedidos')
            ->where('pedido_id', $pedidoId)
            ->first();
    }

    /**
     * Buscar o obtener logo_pedido por mÃºltiples criterios
     * Intenta primero por ID primaria, luego por pedido_id
     * 
     * @param int $pedidoId ID a buscar
     * @return array|null
     */
    public function obtenerPorIdOPedidoId(int $pedidoId): ?array
    {
        // Intentar buscar por ID primaria primero
        $logoPedido = $this->obtenerPorId($pedidoId);

        // Si no encuentra, buscar por pedido_id
        if (!$logoPedido) {
            $logoPedido = $this->obtenerPorPedidoId($pedidoId);
        }

        return $logoPedido;
    }

    /**
     * Crear nuevo registro en logo_pedidos
     * 
     * @param array $datos Datos a insertar
     * @return int ID del registro creado
     */
    public function crear(array $datos): int
    {
        return DB::table('logo_pedidos')->insertGetId($datos);
    }

    /**
     * Actualizar logo_pedido existente
     * 
     * @param int $id ID del logo_pedido
     * @param array $datos Datos a actualizar
     * @return bool
     */
    public function actualizar(int $id, array $datos): bool
    {
        return DB::table('logo_pedidos')
            ->where('id', $id)
            ->update($datos) > 0;
    }

    /**
     * Agregar foto a logo_pedido
     * 
     * @param int $logoPedidoId ID del logo_pedido
     * @param int $logoFotoCotizacionId ID de la foto
     * @param int $orden Orden de la foto
     * @return bool
     */
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

    /**
     * Obtener todas las fotos de un logo_pedido
     * 
     * @param int $logoPedidoId ID del logo_pedido
     * @return array
     */
    public function obtenerFotos(int $logoPedidoId): array
    {
        return DB::table('logo_pedido_fotos')
            ->where('logo_pedido_id', $logoPedidoId)
            ->orderBy('orden')
            ->get()
            ->toArray();
    }

    /**
     * Verificar si existe logo_pedido
     * 
     * @param int $id ID a verificar
     * @return bool
     */
    public function existe(int $id): bool
    {
        return DB::table('logo_pedidos')
            ->where('id', $id)
            ->exists();
    }

    /**
     * Obtener datos completos incluyendo fotos
     * 
     * @param int $id ID del logo_pedido
     * @return array|null
     */
    public function obtenerCompleto(int $id): ?array
    {
        $logoPedido = $this->obtenerPorId($id);

        if (!$logoPedido) {
            return null;
        }

        // Agregar fotos
        $logoPedido->fotos = $this->obtenerFotos($id);

        return $logoPedido;
    }
}

