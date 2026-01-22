<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: PedidosProcesosPrendaTalla
 * 
 * Talla relacional para un proceso de prenda.
 * Soporta DAMA, CABALLERO, UNISEX como géneros.
 * Cada registro = genero + talla + cantidad en un proceso específico.
 */
class PedidosProcesosPrendaTalla extends Model
{
    protected $table = 'pedidos_procesos_prenda_tallas';

    protected $fillable = [
        'proceso_prenda_detalle_id',
        'genero',
        'talla',
        'cantidad',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

    /**
     * Proceso de prenda al cual pertenece esta talla
     */
    public function procesoPrendaDetalle(): BelongsTo
    {
        return $this->belongsTo(PedidosProcesosPrendaDetalle::class, 'proceso_prenda_detalle_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Filtrar por género
     */
    public function scopeGenero($query, $genero)
    {
        return $query->where('genero', $genero);
    }

    /**
     * Filtrar por talla
     */
    public function scopeTalla($query, $talla)
    {
        return $query->where('talla', $talla);
    }

    /**
     * Filtrar por proceso
     */
    public function scopeProceso($query, $procesoPrendaDetalleId)
    {
        return $query->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId);
    }

    /**
     * Filtrar solo registros con cantidad > 0
     */
    public function scopeConCantidad($query)
    {
        return $query->where('cantidad', '>', 0);
    }
}
