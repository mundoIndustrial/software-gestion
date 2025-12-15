<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PrendaTalaPed Model
 * 
 * Representa una talla de una prenda en un pedido
 * Equivalente a PrendaTalaCot pero para pedidos
 */
class PrendaTalaPed extends Model
{
    use SoftDeletes;

    protected $table = 'prenda_tallas_ped';
    protected $guarded = [];

    /**
     * Relación: Pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPed::class, 'prenda_ped_id');
    }
}
