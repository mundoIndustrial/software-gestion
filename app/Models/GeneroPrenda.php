<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneroPrenda extends Model
{
    protected $table = 'generos_prenda';
    protected $fillable = ['nombre', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
