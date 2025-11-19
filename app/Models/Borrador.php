<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Borrador extends Model
{
    use SoftDeletes;

    protected $table = 'borradores';

    protected $fillable = [
        'user_id',
        'cliente',
        'datos_cotizacion',
        'productos',
        'tecnicas',
        'ubicaciones',
        'bordado_estampado',
        'especificaciones',
        'imagenes',
        'notas'
    ];

    protected $casts = [
        'datos_cotizacion' => 'array',
        'productos' => 'array',
        'tecnicas' => 'array',
        'ubicaciones' => 'array',
        'bordado_estampado' => 'array',
        'especificaciones' => 'array',
        'imagenes' => 'array',
    ];

    // Relación con usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
