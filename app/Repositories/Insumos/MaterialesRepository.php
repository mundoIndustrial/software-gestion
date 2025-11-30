<?php

namespace App\Repositories\Insumos;

use App\Models\MaterialesOrdenInsumos;
use App\Models\PedidoProduccion;

/**
 * Repository para gestión de materiales de órdenes
 * Centraliza todas las operaciones de acceso a datos para materiales
 */
class MaterialesRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new MaterialesOrdenInsumos();
    }

    /**
     * Obtener todos los materiales de una orden
     */
    public function obtenerPorNumeroPedido($numeroPedido)
    {
        return MaterialesOrdenInsumos::where('numero_pedido', $numeroPedido)
            ->get();
    }

    /**
     * Obtener materiales paginados con filtros
     * NO joinea porque descripcion_prendas es un atributo appended, no una columna
     * Se obtiene a través de la relación con PedidoProduccion
     */
    public function obtenerConFiltros($filtros = [], $perPage = 25)
    {
        $query = MaterialesOrdenInsumos::query()
            ->with('pedido') // Eager load la relación para obtener descripcion_prendas
            ->distinct();

        // Aplicar filtros
        if (!empty($filtros['numero_pedido'])) {
            $query->where('numero_pedido', $filtros['numero_pedido']);
        }

        if (!empty($filtros['nombre_material'])) {
            $query->where('nombre_material', 'LIKE', '%' . $filtros['nombre_material'] . '%');
        }

        if (isset($filtros['recibido'])) {
            $query->where('recibido', $filtros['recibido']);
        }

        if (!empty($filtros['search'])) {
            $query->where(function($q) use ($filtros) {
                $q->where('nombre_material', 'LIKE', '%' . $filtros['search'] . '%')
                  ->orWhere('observaciones', 'LIKE', '%' . $filtros['search'] . '%');
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Crear o actualizar un material
     */
    public function createOrUpdate($data, $orderId = null)
    {
        return MaterialesOrdenInsumos::updateOrCreate(
            [
                'numero_pedido' => $data['numero_pedido'],
                'prenda_pedido_id' => $data['prenda_pedido_id'] ?? null,
            ],
            $data
        );
    }

    /**
     * Eliminar un material
     */
    public function delete($numeroPedido, $prendaPedidoId)
    {
        return MaterialesOrdenInsumos::where([
            'numero_pedido' => $numeroPedido,
            'prenda_pedido_id' => $prendaPedidoId,
        ])->delete();
    }

    /**
     * Obtener valores únicos de una columna
     */
    public function obtenerValoresUnicos($column, $filtros = [])
    {
        $query = MaterialesOrdenInsumos::query();

        // Aplicar filtros base
        if (!empty($filtros['estado'])) {
            $query->whereIn('estado', $filtros['estado']);
        }

        if (!empty($filtros['area'])) {
            $query->where(function($q) use ($filtros) {
                foreach ($filtros['area'] as $area) {
                    $q->orWhere('area', 'LIKE', '%' . $area . '%');
                }
            });
        }

        return $query->distinct($column)
            ->pluck($column)
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Contar materiales con filtros
     */
    public function contar($filtros = [])
    {
        $query = MaterialesOrdenInsumos::query();

        if (!empty($filtros['numero_pedido'])) {
            $query->where('numero_pedido', $filtros['numero_pedido']);
        }

        if (isset($filtros['recibido'])) {
            $query->where('recibido', $filtros['recibido']);
        }

        return $query->count();
    }

    /**
     * Obtener materiales por área
     */
    public function obtenerPorArea($area)
    {
        return MaterialesOrdenInsumos::where('area', 'LIKE', '%' . $area . '%')
            ->get();
    }

    /**
     * Obtener materiales por estado
     */
    public function obtenerPorEstado($estado)
    {
        return MaterialesOrdenInsumos::where('estado', $estado)->get();
    }
}
