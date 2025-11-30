<?php

namespace Modules\Insumos\Backend\Repositories;

use App\Models\MaterialesOrdenInsumos;

class MaterialesRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new MaterialesOrdenInsumos();
    }

    /**
     * Obtener todos los materiales
     */
    public function obtenerTodos()
    {
        return $this->model->all();
    }

    /**
     * Obtener materiales por número de pedido
     */
    public function obtenerPorNumeroPedido($numeroPedido)
    {
        return $this->model->where('numero_pedido', $numeroPedido)->get();
    }

    /**
     * Obtener materiales con filtros
     */
    public function obtenerConFiltros($filtros = [])
    {
        $query = $this->model->query();

        if (!empty($filtros['numero_pedido'])) {
            $query->where('numero_pedido', $filtros['numero_pedido']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['area'])) {
            $query->where('area', $filtros['area']);
        }

        if (!empty($filtros['buscar'])) {
            $query->where(function ($q) use ($filtros) {
                $q->where('nombre_insumo', 'like', "%{$filtros['buscar']}%")
                  ->orWhere('observaciones', 'like', "%{$filtros['buscar']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Crear o actualizar material
     */
    public function createOrUpdate($data)
    {
        if (isset($data['id'])) {
            return $this->model->find($data['id'])->update($data);
        }
        return $this->model->create($data);
    }

    /**
     * Eliminar material
     */
    public function delete($id)
    {
        return $this->model->find($id)->delete();
    }

    /**
     * Obtener valores únicos de una columna
     */
    public function obtenerValoresUnicos($column)
    {
        return $this->model->distinct()->pluck($column)->filter()->values();
    }

    /**
     * Contar materiales
     */
    public function contar($filtros = [])
    {
        $query = $this->model->query();

        if (!empty($filtros['numero_pedido'])) {
            $query->where('numero_pedido', $filtros['numero_pedido']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        return $query->count();
    }

    /**
     * Obtener materiales por área
     */
    public function obtenerPorArea($area)
    {
        return $this->model->where('area', $area)->get();
    }

    /**
     * Obtener materiales por estado
     */
    public function obtenerPorEstado($estado)
    {
        return $this->model->where('estado', $estado)->get();
    }

    /**
     * Obtener material por ID
     */
    public function obtenerPorId($id)
    {
        return $this->model->find($id);
    }

    /**
     * Obtener estadísticas por área
     */
    public function obtenerEstadisticasPorArea()
    {
        return $this->model->selectRaw('area, COUNT(*) as total')
            ->groupBy('area')
            ->pluck('total', 'area');
    }
}
