<?php

namespace App\Application\Bodega\Services;

use App\Models\PedidoOculto;
use App\Models\PedidoRevisado;
use App\Models\PedidoProduccion;
use Illuminate\Http\Request;

class PedidoEstadoService
{
    private const ITEMS_PER_PAGE = 20;

    public function ocultarPedido(int $pedidoId): array
    {
        try {
            $userId = auth()->id();

            PedidoOculto::firstOrCreate([
                'pedido_id' => $pedidoId,
                'user_id' => $userId
            ]);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deshacerOcultarPedido(int $pedidoId): array
    {
        try {
            $userId = auth()->id();

            PedidoOculto::where('pedido_id', $pedidoId)
                ->where('user_id', $userId)
                ->delete();

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function marcarComoRevisado(int $pedidoId, bool $revisado): array
    {
        try {
            $userId = auth()->id();

            if ($revisado) {
                PedidoRevisado::firstOrCreate([
                    'pedido_id' => $pedidoId,
                    'user_id' => $userId
                ]);
            } else {
                PedidoRevisado::where('pedido_id', $pedidoId)
                    ->where('user_id', $userId)
                    ->delete();
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function obtenerPedidosOcultos(Request $request): array
    {
        try {
            $userId = auth()->id();
            $search = $request->query('search', '');
            $page = $request->query('page', 1);

            $pedidosOcultosIds = PedidoOculto::where('user_id', $userId)
                ->pluck('pedido_id')
                ->toArray();

            $query = PedidoProduccion::whereIn('id', $pedidosOcultosIds);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('numero_pedido', 'LIKE', '%' . $search . '%')
                      ->orWhere('cliente', 'LIKE', '%' . $search . '%');
                });
            }

            $pedidosPaginados = $query->paginate(self::ITEMS_PER_PAGE, ['*'], 'page', $page);

            $datos = $this->formatearPedidosOcultos($pedidosPaginados->items(), $userId);

            return [
                'success' => true,
                'pedidosPorPagina' => $datos,
                'totalPedidos' => $pedidosPaginados->total(),
                'paginaActual' => $pedidosPaginados->currentPage(),
                'porPagina' => self::ITEMS_PER_PAGE,
                'search' => $search,
                'routeName' => 'gestion-bodega.pedidos-ocultos'
            ];
        } catch (\Exception $e) {
            \Log::error('Error en obtenerPedidosOcultos: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function formatearPedidosOcultos($pedidos, int $userId): array
    {
        $datos = [];

        foreach ($pedidos as $pedido) {
            $datos[] = [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'asesor' => $pedido->asesor ? $pedido->asesor->name : '—',
                'fecha_pedido' => $pedido->created_at,
                'fecha_actualizacion' => $pedido->updated_at,
                'pedido_revisado' => PedidoRevisado::where('pedido_id', $pedido->id)
                    ->where('user_id', $userId)
                    ->exists(),
                'tiene_cambios_nuevos' => false,
                'todos_pendientes' => false,
                'todos_entregados' => false
            ];
        }

        return $datos;
    }

    public function obtenerStatusCode(array $result): int
    {
        if ($result['success']) {
            return 200;
        }

        if (($result['message'] ?? '') === 'No tienes permisos para realizar esta acción.') {
            return 403;
        }

        return 400;
    }
}
