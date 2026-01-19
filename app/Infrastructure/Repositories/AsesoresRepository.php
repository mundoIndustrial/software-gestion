<?php

namespace App\Infrastructure\Repositories;

use App\Models\PedidoProduccion;
use App\Models\LogoPedido;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * AsesoresRepository
 * 
 * Repositorio para acceso a datos de asesores
 * Responsabilidad: Encapsular todas las queries relacionadas con asesores y sus pedidos
 */
class AsesoresRepository
{
    /**
     * Obtener pedidos de producción del asesor
     */
    public function obtenerPedidosProduccion(array $filtros = []): LengthAwarePaginator
    {
        $userId = Auth::id();
        
        $query = PedidoProduccion::query()
            ->where('asesor_id', $userId)
            ->with([
                'prendas' => function ($q) {
                    $q->with(['procesos' => function ($q2) {
                        $q2->orderBy('created_at', 'desc');
                    }]);
                },
                'asesora',
                'logoPedidos'
            ]);

        // Filtros
        if (!empty($filtros['estado'])) {
            if ($filtros['estado'] === 'No iniciado') {
                $query->where('estado', 'No iniciado')
                      ->whereNull('aprobado_por_supervisor_en');
            } elseif ($filtros['estado'] === 'En Ejecución') {
                $query->whereIn('estado', ['No iniciado', 'En Ejecución']);
            } else {
                $query->where('estado', $filtros['estado']);
            }
        }

        if (!empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function($q) use ($search) {
                $q->where('numero_pedido', 'LIKE', "%{$search}%")
                  ->orWhere('cliente', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Obtener logos pedidos del asesor
     */
    public function obtenerLogoPedidos(array $filtros = []): LengthAwarePaginator
    {
        $userName = Auth::user()->name ?? 'Sin nombre';
        
        $query = LogoPedido::query()
            ->where(function($q) use ($userName) {
                $q->where('asesora', $userName)
                  ->orWhereNull('asesora');
            })
            ->with('procesos');

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function($q) use ($search) {
                $q->where('numero_pedido', 'LIKE', "%{$search}%")
                  ->orWhere('cliente', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Obtener estados únicos de pedidos
     */
    public function obtenerEstados(): array
    {
        return PedidoProduccion::select('estado')
            ->whereNotNull('estado')
            ->distinct()
            ->pluck('estado')
            ->toArray();
    }

    /**
     * Obtener pedido por número
     */
    public function obtenerPorNumeroPedido(int $numeroPedido): ?PedidoProduccion
    {
        return PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->where('asesor_id', Auth::id())
            ->with('prendas')
            ->first();
    }

    /**
     * Obtener logo pedido por número
     */
    public function obtenerLogoPorNumero(int $numeroPedido): ?LogoPedido
    {
        return LogoPedido::where('numero_pedido', $numeroPedido)->first();
    }

    /**
     * Verificar si el asesor es propietario del pedido
     */
    public function esDelAsesor(int $pedidoId): bool
    {
        $pedido = PedidoProduccion::find($pedidoId);
        return $pedido && $pedido->asesor_id === Auth::id();
    }
}
