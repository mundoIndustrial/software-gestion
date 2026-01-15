<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    protected $table = 'personal';
    
    protected $fillable = [
        'codigo_persona',
        'nombre_persona',
        'id_rol',
    ];

    /**
     * Relación con el modelo Role
     */
    public function rol()
    {
        return $this->belongsTo(Role::class, 'id_rol');
    }
}
