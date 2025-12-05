<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class EntregaPedidoCostura extends Model
{
    use Auditable;
    protected $table = 'entregas_pedido_costura';
    
    public $timestamps = false;

    protected $fillable = [
        'pedido',
        'cliente',
        'prenda',
        'descripcion',
        'talla',
        'cantidad_entregada',
        'fecha_entrega',
        'costurero',
        'mes_ano'
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
        'pedido' => 'integer',
        'cantidad_entregada' => 'integer',
    ];
}
