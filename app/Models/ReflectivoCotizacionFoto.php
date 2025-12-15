<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReflectivoCotizacionFoto extends Model
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
    ];

    /**
     * Relación: Una foto pertenece a un reflectivo_cotizacion
     */
    public function reflectivoCotizacion(): BelongsTo
    {
        return $this->belongsTo(ReflectivoCotizacion::class);
    }
}
