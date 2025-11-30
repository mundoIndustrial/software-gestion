<?php

namespace Modules\Insumos\Backend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaterialesOrdenInsumos extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla
     */
    protected $table = 'materiales_orden_insumos';

    /**
     * Atributos que se pueden asignar en masa
     */
    protected $fillable = [
        'numero_pedido',
        'nombre_insumo',
        'cantidad',
        'estado',
        'area',
        'observaciones',
        'asignado_a',
    ];

    /**
     * Atributos con casting
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con orden de producción
     */
    public function orden()
    {
        return $this->belongsTo(PedidoProduccion::class, 'numero_pedido', 'numero_pedido');
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeByEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar por área
     */
    public function scopeByArea($query, $area)
    {
        return $query->where('area', $area);
    }

    /**
     * Scope para filtrar por pedido
     */
    public function scopeByNumeroPedido($query, $numeroPedido)
    {
        return $query->where('numero_pedido', $numeroPedido);
    }

    /**
     * Scope para búsqueda de insumo
     */
    public function scopeSearchInsumo($query, $search)
    {
        return $query->where('nombre_insumo', 'like', "%{$search}%")
            ->orWhere('observaciones', 'like', "%{$search}%");
    }
}
