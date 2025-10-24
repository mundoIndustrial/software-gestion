<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntregaPedidoCorte extends Model
{
    protected $table = 'entrega_pedido_corte';
    
    public $timestamps = false;

    protected $fillable = [
        'pedido',
        'prenda',
        'cortador',
        'cantidad_prendas',
        'piezas',
        'pasadas',
        'etiqueteadas',
        'etiquetador',
        'fecha_entrega',
        'mes',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
        'cantidad_prendas' => 'integer',
        'piezas' => 'integer',
        'pasadas' => 'integer',
        'etiqueteadas' => 'integer',
    ];
}
