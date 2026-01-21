<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TipoBrocheBoton Model
 * 
 * Apunta a la tabla tipos_broche_boton que contiene los tipos de broches y botones
 */
class TipoBrocheBoton extends Model
{
    protected $table = 'tipos_broche_boton';
    
    protected $fillable = ['nombre', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
