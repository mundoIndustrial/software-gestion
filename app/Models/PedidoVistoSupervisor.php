<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoVistoSupervisor extends Model
{
    use HasFactory;

    protected $table = 'pedidos_vistos_supervisor';

    public $timestamps = false;

    protected $fillable = [
        'pedido_id',
        'user_id',
    ];

    /**
     * Get the pedido that owns the PedidoVistoSupervisor.
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_id');
    }

    /**
     * Get the user that owns the PedidoVistoSupervisor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
