<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PrendaFotoPed Model
 * 
 * Representa una foto de prenda en un pedido
 * Equivalente a PrendaFotoCot pero para pedidos
 */
class PrendaFotoPed extends Model
{
    use SoftDeletes;

    protected $table = 'prenda_fotos_ped';
    protected $guarded = [];

    /**
     * Relación: Pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPed::class, 'prenda_ped_id');
    }
}
