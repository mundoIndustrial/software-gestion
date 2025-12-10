<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaTelaCot extends Model
{
    protected $table = 'prenda_telas_cot';

    protected $fillable = [
        'prenda_cot_id',
        'color',
        'nombre_tela',
        'referencia',
        'url_imagen',
    ];

    /**
     * Relación: Una tela pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }
}
