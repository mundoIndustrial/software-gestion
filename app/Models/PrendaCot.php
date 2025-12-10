<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrendaCot extends Model
{
    protected $table = 'prendas_cot';

    protected $fillable = [
        'cotizacion_id',
        'nombre_producto',
        'descripcion',
        'cantidad',
        'tipo_prenda',
        'es_jean_pantalon',
        'tipo_jean_pantalon',
        'genero',
        'color',
        'tiene_bolsillos',
        'obs_bolsillos',
        'aplica_manga',
        'tipo_manga',
        'obs_manga',
        'aplica_broche',
        'tipo_broche_id',
        'obs_broche',
        'tiene_reflectivo',
        'obs_reflectivo',
        'descripcion_adicional',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'es_jean_pantalon' => 'boolean',
        'tiene_bolsillos' => 'boolean',
        'aplica_manga' => 'boolean',
        'aplica_broche' => 'boolean',
        'tiene_reflectivo' => 'boolean',
    ];

    /**
     * Relación: Una prenda pertenece a una cotización
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    /**
     * Relación: Una prenda puede tener múltiples fotos
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(PrendaFotoCot::class, 'prenda_cot_id');
    }

    /**
     * Relación: Una prenda puede tener múltiples telas
     */
    public function telas(): HasMany
    {
        return $this->hasMany(PrendaTelaCot::class, 'prenda_cot_id');
    }

    /**
     * Relación: Una prenda puede tener múltiples tallas
     */
    public function tallas(): HasMany
    {
        return $this->hasMany(PrendaTallaCot::class, 'prenda_cot_id');
    }

    /**
     * Relación: Una prenda puede tener múltiples variantes
     */
    public function variantes(): HasMany
    {
        return $this->hasMany(PrendaVarianteCot::class, 'prenda_cot_id');
    }
}
