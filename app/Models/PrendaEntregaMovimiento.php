<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaEntregaMovimiento extends Model
{
    use HasFactory;

    protected $table = 'prenda_entrega_movimientos';

    protected $fillable = [
        'prenda_pedido_id',
        'consecutivo_recibo_id',
        'cantidad_entregada',
        'detalle_tallas',
        'fecha_entrega',
        'usuario_id',
    ];

    protected $casts = [
        'cantidad_entregada' => 'integer',
        'detalle_tallas' => 'array',
        'fecha_entrega' => 'datetime',
    ];

    public function prendaPedido(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    public function consecutivoRecibo(): BelongsTo
    {
        return $this->belongsTo(ConsecutivoReciboPedido::class, 'consecutivo_recibo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
