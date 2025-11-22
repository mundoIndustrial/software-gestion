<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoManga extends Model
{
    protected $table = 'tipos_manga';
    protected $fillable = ['nombre', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
