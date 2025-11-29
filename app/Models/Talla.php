<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Talla extends Model
{
    protected $table = 'tallas';

    protected $fillable = [
        'nombre',
    ];

    /**
     * Relación muchos a muchos con PrendaMetraje
     * Una talla puede estar en muchas prendas
     * Una prenda puede tener muchas tallas
     */
    public function prendas(): BelongsToMany
    {
        return $this->belongsToMany(
            PrendaMetraje::class,
            'talla_metraje',
            'talla_id',
            'prenda_metraje_id'
        )->withPivot('metros')->withTimestamps();
    }
}
