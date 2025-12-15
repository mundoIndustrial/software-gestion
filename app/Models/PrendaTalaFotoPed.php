<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PrendaTalaFotoPed Model
 * 
 * Representa una foto de tela en un pedido
 * Equivalente a PrendaTalaFotoCot pero para pedidos
 */
class PrendaTalaFotoPed extends Model
{
    use SoftDeletes;

    protected $table = 'prenda_tela_fotos_ped';
    protected $guarded = [];

    /**
     * Relación: Pertenece a una tela
     */
    public function tela(): BelongsTo
    {
        return $this->belongsTo(PrendaTelaPed::class, 'prenda_tela_ped_id');
    }
}
