<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PrendaVariantePed Model
 * 
 * Representa una variante de una prenda en un pedido
 * Equivalente a PrendaVarianteCot pero para pedidos
 */
class PrendaVariantePed extends Model
{
    use SoftDeletes;

    protected $table = 'prenda_variantes_ped';
    protected $guarded = [];
    protected $casts = [
        'telas_multiples' => 'json',
    ];

    /**
     * Relación: Pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPed::class, 'prenda_ped_id');
    }
}
