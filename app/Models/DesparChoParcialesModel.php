<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DesparChoParcialesModel (Eloquent Model - Infrastructure Layer)
 * 
 * Modelo de persistencia para despachos parciales.
 * Responsable de la comunicación con la base de datos.
 */
class DesparChoParcialesModel extends Model
{
    use SoftDeletes;

    protected $table = 'despacho_parciales';

    protected $fillable = [
        'pedido_id',
        'tipo_item',
        'item_id',
        'talla_id',
        'genero',
        'pendiente_inicial',
        'parcial_1',
        'pendiente_1',
        'parcial_2',
        'pendiente_2',
        'parcial_3',
        'pendiente_3',
        'observaciones',
        'fecha_despacho',
        'usuario_id',
        'entregado',
        'fecha_entrega',
    ];

    protected $casts = [
        'fecha_despacho' => 'datetime',
        'entregado' => 'boolean',
        'fecha_entrega' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'pendiente_inicial' => 0,
        'parcial_1' => 0,
        'pendiente_1' => 0,
        'parcial_2' => 0,
        'pendiente_2' => 0,
        'parcial_3' => 0,
        'pendiente_3' => 0,
    ];

    // ============ RELACIONES ============

    /**
     * Relación: Pertenece a un pedido
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_id', 'id');
    }

    /**
     * Relación: Usuario que registró el despacho
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id');
    }

    // ============ SCOPES ============

    /**
     * Scope: Filtrar por tipo de ítem
     */
    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_item', $tipo);
    }

    /**
     * Scope: Filtrar por pedido
     */
    public function scopePorPedido($query, int $pedidoId)
    {
        return $query->where('pedido_id', $pedidoId);
    }

    /**
     * Scope: Filtrar por ítem específico
     */
    public function scopePorItem($query, string $tipoItem, int $itemId)
    {
        return $query->where('tipo_item', $tipoItem)
                     ->where('item_id', $itemId);
    }

    /**
     * Scope: No eliminados
     */
    public function scopeActivo($query)
    {
        return $query->whereNull('deleted_at');
    }

    // ============ MÉTODOS HELPER ============

    /**
     * Obtener total despachado
     */
    public function totalDespachado(): int
    {
        return $this->parcial_1 + $this->parcial_2 + $this->parcial_3;
    }

    /**
     * Verificar si está completamente despachado
     */
    public function estaCompletoDespachado(int $cantidadTotal): bool
    {
        return $this->totalDespachado() >= $cantidadTotal;
    }
}
