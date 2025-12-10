<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaVarianteCot extends Model
{
    protected $table = 'prenda_variantes_cot';

    protected $fillable = [
        'prenda_cot_id',
        'tipo_prenda',
        'es_jean_pantalon',
        'tipo_jean_pantalon',
        'genero_id',
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
        'es_jean_pantalon' => 'boolean',
        'tiene_bolsillos' => 'boolean',
        'aplica_manga' => 'boolean',
        'aplica_broche' => 'boolean',
        'tiene_reflectivo' => 'boolean',
    ];

    /**
     * Relación: Una variante pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }

    /**
     * Relación: Una variante tiene un género
     */
    public function genero(): BelongsTo
    {
        return $this->belongsTo(GeneroPrend::class, 'genero_id');
    }
}
