<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReflectivofotoCotizacion extends Model
{
    use SoftDeletes;

    protected $table = 'reflectivo_fotos_cotizacion';

    protected $fillable = [
        'reflectivo_cotizacion_id',
        'ruta_original',
        'ruta_webp',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación: Una foto pertenece a un reflectivo
     */
    public function reflectivo()
    {
        return $this->belongsTo(ReflectivoCotizacion::class, 'reflectivo_cotizacion_id');
    }
}
