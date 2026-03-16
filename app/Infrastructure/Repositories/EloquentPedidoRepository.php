<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Pedidos\Contracts\PedidoRepository;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * EloquentPedidoRepository
 * 
 * Implementación de PedidoRepository usando Eloquent
 */
class EloquentPedidoRepository implements PedidoRepository
{
    public function obtenerPorId(int $id): ?PedidoProduccion
    {
        try {
            return PedidoProduccion::find($id);
        } catch (\Exception $e) {
            Log::error('[EloquentPedidoRepository] Error obtener por ID', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function obtenerPorNumero(int $numero): ?PedidoProduccion
    {
        try {
            return PedidoProduccion::where('numero_pedido', $numero)->first();
        } catch (\Exception $e) {
            Log::error('[EloquentPedidoRepository] Error obtener por número', [
                'numero' => $numero,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function obtenerConRelaciones(int $id, array $relaciones = []): ?PedidoProduccion
    {
        try {
            $query = PedidoProduccion::query();

            if (!empty($relaciones)) {
                $query->with($relaciones);
            }

            return $query->find($id);

        } catch (\Exception $e) {
            Log::error('[EloquentPedidoRepository] Error obtener con relaciones', [
                'id' => $id,
                'relaciones' => $relaciones,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function listarPorCliente(int $clienteId): array
    {
        try {
            return PedidoProduccion::where('cliente_id', $clienteId)->get()->toArray();
        } catch (\Exception $e) {
            Log::error('[EloquentPedidoRepository] Error listar por cliente', [
                'cliente_id' => $clienteId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
