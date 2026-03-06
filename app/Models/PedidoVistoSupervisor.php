<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoVistoSupervisor extends Model
{
    protected $table = 'pedidos_vistos_supervisor';

    public $timestamps = false;

    protected $fillable = [
        'pedido_id',
        'user_id',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
