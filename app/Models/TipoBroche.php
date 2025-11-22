<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoBroche extends Model
{
    protected $table = 'tipos_broche';
    protected $fillable = ['nombre', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
