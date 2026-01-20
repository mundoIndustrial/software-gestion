<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TipoBroche Model
 * 
 * Apunta a la tabla tipos_broche que contiene los tipos de broches y botones
 */
class TipoBroche extends Model
{
    protected $table = 'tipos_broche';
    
    protected $fillable = ['nombre', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
