<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaFotoCot extends Model
{
    protected $table = 'prenda_fotos_cot';

    protected $fillable = [
        'prenda_cot_id',
        'ruta_original',
        'ruta_webp',
        'ruta_miniatura',
        'orden',
        'ancho',
        'alto',
        'tamaño',
    ];

    /**
     * Relación: Una foto pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }
}
