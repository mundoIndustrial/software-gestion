<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioPorRol extends Model
{
    protected $table = 'horario_por_roles';

    protected $fillable = [
        'id_rol',
        'entrada_manana',
        'salida_manana',
        'entrada_tarde',
        'salida_tarde',
    ];

    protected $casts = [
        'entrada_manana' => 'string',
        'salida_manana' => 'string',
        'entrada_tarde' => 'string',
        'salida_tarde' => 'string',
    ];

    /**
     * Relación con el modelo Role
     */
    public function rol()
    {
        return $this->belongsTo(Role::class, 'id_rol');
    }
}
