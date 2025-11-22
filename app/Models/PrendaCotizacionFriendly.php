<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaCotizacionFriendly extends Model
{
    protected $table = 'prendas_cotizaciones';

    protected $fillable = [
        'cotizacion_id',
        'nombre_producto',
        'es_jean_pantalon',
        'tipo_jean_pantalon',
        'descripcion',
        'tallas',
        'fotos',
        'imagen_tela',
        'estado'
    ];

    protected $casts = [
        'tallas' => 'array',
        'fotos' => 'array',
        'es_jean_pantalon' => 'boolean'
    ];

    /**
     * Relación con Cotizacion
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
