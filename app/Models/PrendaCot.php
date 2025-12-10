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
    ];

    protected $casts = [
        'cantidad' => 'integer',
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
