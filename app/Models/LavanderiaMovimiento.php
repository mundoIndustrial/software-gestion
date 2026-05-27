<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LavanderiaMovimiento extends Model
{
    protected $table = 'lavanderia_movimientos';

    protected $fillable = [
        'consecutivo_recibo_pedido_id',
        'numero_recibo',
        'tipo_recibo',
        'tipo_movimiento',
        'fecha_movimiento',
        'firma_movimiento',
        'novedad',
        'estado',
    ];

    protected $casts = [
        'fecha_movimiento' => 'datetime',
    ];

    /**
     * Relación: Un movimiento pertenece a un recibo
     */
    public function consecutivoRecibo(): BelongsTo
    {
        return $this->belongsTo(ConsecutivoReciboPedido::class, 'consecutivo_recibo_pedido_id');
    }

    /**
     * Relación: Un movimiento tiene muchas tallas
     */
    public function tallas(): HasMany
    {
        return $this->hasMany(LavanderiaMovimientoTalla::class, 'lavanderia_movimiento_id');
    }
}
