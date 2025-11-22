<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColorPrenda extends Model
{
    protected $table = 'colores_prenda';
    protected $fillable = ['nombre', 'codigo', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
