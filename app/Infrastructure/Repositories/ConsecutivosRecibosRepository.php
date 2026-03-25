<?php

namespace App\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * ConsecutivosRecibosRepository
 * 
 * Acceso a datos para consecutivos de recibos de pedidos
 * Cumple DDD: Infrastructure Layer - Repository Pattern
 */
class ConsecutivosRecibosRepository
{
    /**
     * Obtener consecutivos por prenda y pedido
     */
    public function obtenerPorPrendaYPedido(int $prendaId, int $pedidoProduccionId)
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->where('prenda_id', $prendaId)
            ->where('pedido_produccion_id', $pedidoProduccionId)
            ->where('activo', 1)
            ->get();
    }

    /**
     * Obtener consecutivo COSTURA para prenda específica
     */
    public function obtenerCosinturaPorPrenda(int $pedidoId, int $prendaId)
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_id', $prendaId)
            ->where('tipo_recibo', 'COSTURA')
            ->where('activo', 1)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Obtener consecutivo COSTURA de un pedido (sin filtrar prenda)
     */
    public function obtenerCosturaDelPedido(int $pedidoId)
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('tipo_recibo', 'COSTURA')
            ->where('activo', 1)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Obtener todos los consecutivos de una prenda
     */
    public function obtenerTodosPorPrenda(int $prendaId, int $pedidoId)
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->where('prenda_id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->where('activo', 1)
            ->get();
    }

    /**
     * Obtener fechas de completado por área
     */
    public function obtenerFechasCompletadoPorArea(int $reciboCosturaId): array
    {
        $rows = DB::table('prenda_recibo_completado')
            ->select(['area', 'fecha_completado'])
            ->where('id_recibo', $reciboCosturaId)
            ->get();

        $resultado = [];
        foreach ($rows as $row) {
            $key = strtolower(trim((string) ($row->area ?? '')));
            if ($key !== '') {
                $resultado[$key] = $row->fecha_completado;
            }
        }

        return $resultado;
    }
}
