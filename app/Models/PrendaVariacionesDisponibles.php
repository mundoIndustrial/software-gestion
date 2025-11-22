<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaVariacionesDisponibles extends Model
{
    protected $table = 'prenda_variaciones_disponibles';

    protected $fillable = [
        'tipo_prenda_id',
        'tiene_manga',
        'tiene_bolsillos',
        'tiene_broche',
        'tiene_reflectivo',
        'tiene_cuello'
    ];

    protected $casts = [
        'tiene_manga' => 'boolean',
        'tiene_bolsillos' => 'boolean',
        'tiene_broche' => 'boolean',
        'tiene_reflectivo' => 'boolean',
        'tiene_cuello' => 'boolean'
    ];

    /**
     * Relación: Pertenece a un tipo de prenda
     */
    public function tipoPrenda(): BelongsTo
    {
        return $this->belongsTo(TipoPrenda::class);
    }
}
