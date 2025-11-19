<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reporte extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'titulo',
        'descripcion',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'datos'
    ];

    protected $casts = [
        'datos' => 'array',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date'
    ];

    // Relación con usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
