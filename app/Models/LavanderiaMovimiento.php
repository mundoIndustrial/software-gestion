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
        'fecha_salida',
        'firma_salida',
        'fecha_llegada',
        'firma_llegada',
        'novedad',
        'estado',
    ];

    protected $casts = [
        'fecha_salida' => 'datetime',
        'fecha_llegada' => 'datetime',
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
