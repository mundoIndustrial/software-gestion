<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrendaCotizacionFriendly extends Model
{
    protected $table = 'prendas_cotizaciones';

    protected $fillable = [
        'cotizacion_id',
        'nombre_producto',
        'genero',
        'es_jean_pantalon',
        'tipo_jean_pantalon',
        'descripcion',
        'tallas',
        'fotos',
        'telas',
        'estado'
    ];

    protected $casts = [
        'tallas' => 'array',
        'fotos' => 'array',
        'telas' => 'array',
        'es_jean_pantalon' => 'boolean'
    ];

    /**
     * Relación con Cotizacion
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    /**
     * Relación con VariantePrenda
     */
    public function variantes(): HasMany
    {
        return $this->hasMany(VariantePrenda::class, 'prenda_cotizacion_id');
    }
}
