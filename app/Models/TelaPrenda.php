<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelaPrenda extends Model
{
    protected $table = 'telas_prenda';
    protected $fillable = ['nombre', 'referencia', 'descripcion', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
