<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrendaBodega extends Model
{
    protected $table = 'prenda_bodega';

    protected $fillable = [
        'numero_recibo',
        'nombre',
        'descripcion',
        'user_id',
    ];

    public function tallas()
    {
        return $this->hasMany(PrendaTallasBodega::class, 'prenda_bodega_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
