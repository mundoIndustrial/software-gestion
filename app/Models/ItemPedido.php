<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: ItemPedido
 * 
 * Mapeo a tabla item_pedidos
 * Mantiene la persistencia de items en pedidos
 */
class ItemPedido extends Model
{
    use HasFactory;

    protected $table = 'item_pedidos';

    protected $fillable = [
        'pedido_id',
        'referencia_id',
        'tipo',
        'orden',
        'nombre',
        'descripcion',
        'datos_presentacion',
    ];

    protected $casts = [
        'pedido_id' => 'integer',
        'referencia_id' => 'integer',
        'orden' => 'integer',
        'datos_presentacion' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    /**
     * Scope para obtener items de un pedido ordenados
     */
    public function scopeDelPedido($query, int $pedidoId)
    {
        return $query->where('pedido_id', $pedidoId)->orderBy('orden', 'asc');
    }

    /**
     * Scope para obtener solo prendas
     */
    public function scopeSoloPrendas($query)
    {
        return $query->where('tipo', 'prenda');
    }

    /**
     * Scope para obtener solo EPPs
     */
    public function scopoSoloEpps($query)
    {
        return $query->where('tipo', 'epp');
    }
}
