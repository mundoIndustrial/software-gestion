<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrendaTallasBodega extends Model
{
    protected $table = 'prenda_tallas_bodega';

    protected $fillable = [
        'prenda_bodega_id',
        'talla',
        'genero',
        'color',
        'cantidad',
    ];

    public function prenda()
    {
        return $this->belongsTo(PrendaBodega::class, 'prenda_bodega_id');
    }
}
