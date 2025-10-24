<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntregaBodegaCostura extends Model
{
    protected $table = 'entregas_bodega_costura';

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
