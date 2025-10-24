<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntregaPedidoCostura extends Model
{
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
        'fecha_entrega' => 'date',
        'pedido' => 'integer',
        'cantidad_entregada' => 'integer',
    ];
}
