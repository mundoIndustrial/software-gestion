<?php

namespace App\Domain\PedidoProduccion\Services;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\Paginator;

/**
 * Servicio para obtener listados de pedidos
 * Responsabilidad: Construir queries según filtros y tipo de pedido
 */
class ListaPedidosService
{
    /**
     * Obtener pedidos de producción del asesor autenticado
     */
    public function obtenerPedidosProduccion(array $filtros = []): Paginator
    {
        $query = PedidoProduccion::query()
            ->with([
                'cotizacion' => function($q) {
                    $q->select('id', 'tipo', 'codigo', 'cliente_id', 'estado');
                },
                'prendas' => function ($q) {
                    $q->with(['color', 'tela', 'tipoManga', 'procesos']);
                }
            ]);

        // Filtrar por asesor
        $query->where('asesor_id', Auth::id());

        // Aplicar filtros si se proporcionan
        if (!empty($filtros['estado'])) {
            $estado = $filtros['estado'];
            
            // Para "En Producción", filtrar por múltiples estados
            if ($estado === 'En Producción') {
                $query->whereIn('estado', ['No iniciado', 'En Ejecución']);
            } else {
                $query->where('estado', $estado);
            }
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Obtener pedidos LOGO del asesor autenticado
     */
    public function obtenerLogoPedidos(array $filtros = []): Paginator
    {
        $query = LogoPedido::query()
            ->with(['cotizacion', 'procesos']);

        // Filtrar por asesora (campo 'asesora' es el nombre del usuario)
        $nombreUsuario = Auth::user()->name;
        $query->where(function($q) use ($nombreUsuario) {
            $q->where('asesora', $nombreUsuario)
              ->orWhereNull('asesora');
        });

        // Aplicar filtros
        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Obtener detalle completo de un pedido de producción
     */
    public function obtenerDetallePedido(int $pedidoId)
    {
        $pedido = PedidoProduccion::with([
            'prendas' => function($q) {
                $q->with('procesos');
            },
            'cotizacion' => function($q) {
                $q->with('prendasCotizaciones');
            }
        ])->findOrFail($pedidoId);

        // Verificar permisos
        if ($pedido->asesor_id !== Auth::id()) {
            abort(403);
        }

        return $pedido;
    }

    /**
     * Obtener plantilla ERP/Factura de pedido
     */
    public function obtenerPlantillaPedido(int $pedidoId)
    {
        return $this->obtenerDetallePedido($pedidoId);
    }
}
