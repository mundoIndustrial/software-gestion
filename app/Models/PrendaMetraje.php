<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PrendaMetraje extends Model
{
    protected $table = 'prendas_metraje';

    protected $fillable = [
        'nombre_prenda',
        'ancho_prenda',
    ];

    protected $casts = [
        'ancho_prenda' => 'decimal:2',
    ];

    /**
     * Relación muchos a muchos con Talla
     * Una prenda puede tener muchas tallas
     * Una talla puede estar en muchas prendas
     */
    public function tallas(): BelongsToMany
    {
        return $this->belongsToMany(
            Talla::class,
            'talla_metraje',
            'prenda_metraje_id',
            'talla_id'
        )->withPivot('metros')->withTimestamps();
    }
}
