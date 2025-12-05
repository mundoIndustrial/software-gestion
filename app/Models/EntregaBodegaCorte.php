<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class EntregaBodegaCorte extends Model
{
    use Auditable;
    protected $table = 'entrega_bodega_corte';

    public $timestamps = false;

    protected $fillable = [
        'pedido',
        'cortador',
        'cantidad_prendas',
        'piezas',
        'pasadas',
        'etiqueteadas',
        'etiquetador',
        'prenda',
        'fecha_entrega',
        'mes'
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
        'cantidad_prendas' => 'integer',
        'piezas' => 'integer',
        'pasadas' => 'integer',
        'etiqueteadas' => 'integer',
    ];
}
