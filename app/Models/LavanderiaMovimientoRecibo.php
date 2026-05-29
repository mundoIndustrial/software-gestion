<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LavanderiaMovimientoRecibo extends Model
{
    protected $table = 'lavanderia_movimiento_recibos';

    protected $fillable = [
        'lavanderia_movimiento_id',
        'consecutivo_recibo_pedido_id',
        'numero_recibo',
        'tipo_recibo',
    ];

    protected $casts = [
        'numero_recibo' => 'integer',
    ];

    /**
     * Relación: Pertenece a un movimiento de lavandería
     */
    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(LavanderiaMovimiento::class, 'lavanderia_movimiento_id');
    }

    /**
     * Relación: Pertenece a un recibo
     */
    public function recibo(): BelongsTo
    {
        return $this->belongsTo(ConsecutivoReciboPedido::class, 'consecutivo_recibo_pedido_id');
    }
}
