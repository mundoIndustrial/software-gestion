<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaVarianteCot extends Model
{
    protected $table = 'prenda_variantes_cot';

    protected $fillable = [
        'prenda_cot_id',
        'genero',
        'tipo_manga',
        'tipo_broche',
        'color',
        'observaciones',
    ];

    /**
     * Relación: Una variante pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }
}
