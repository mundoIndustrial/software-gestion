<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaTallaCot extends Model
{
    protected $table = 'prenda_tallas_cot';

    protected $fillable = [
        'prenda_cot_id',
        'talla',
        'color',
        'cantidad',
        'genero_id',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    /**
     * Relación: Una talla pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }

    /**
     * Relación: Una talla pertenece a un género
     */
    public function genero(): BelongsTo
    {
        return $this->belongsTo(GeneroPrenda::class, 'genero_id');
    }
}
