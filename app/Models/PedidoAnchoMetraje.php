<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoAnchoMetraje extends Model
{
    protected $table = 'pedido_ancho_metraje';

    protected $fillable = [
        'pedido_produccion_id',
        'prenda_pedido_id',
        'ancho',
        'metraje',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'ancho' => 'decimal:2',
        'metraje' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece a un PedidoProduccion
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Pertenece a una PrendaPedido
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Usuario que creó el registro
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Relación: Usuario que actualizó el registro
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
