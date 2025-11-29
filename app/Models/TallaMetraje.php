<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TallaMetraje extends Model
{
    protected $table = 'talla_metraje';

    protected $fillable = [
        'prenda_metraje_id',
        'talla_id',
        'metros',
    ];

    protected $casts = [
        'metros' => 'decimal:2',
    ];

    /**
     * Relación con PrendaMetraje
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaMetraje::class, 'prenda_metraje_id');
    }

    /**
     * Relación con Talla
     */
    public function talla(): BelongsTo
    {
        return $this->belongsTo(Talla::class, 'talla_id');
    }
}
