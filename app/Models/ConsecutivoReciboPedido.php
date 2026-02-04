<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsecutivoReciboPedido extends Model
{
    protected $table = 'consecutivos_recibos_pedidos';

    protected $fillable = [
        'pedido_produccion_id',
        'prenda_id',
        'tipo_recibo',
        'consecutivo_actual',
        'consecutivo_inicial',
        'activo',
        'notas',
    ];

    protected $casts = [
        'consecutivo_actual' => 'integer',
        'consecutivo_inicial' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Relación: Un consecutivo pertenece a un pedido
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Un consecutivo pertenece a una prenda (opcional)
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_id');
    }
}
