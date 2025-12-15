<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PrendaTelaPed Model
 * 
 * Representa una tela/color de una prenda en un pedido
 * Equivalente a PrendaTelaCot pero para pedidos
 */
class PrendaTelaPed extends Model
{
    use SoftDeletes;

    protected $table = 'prenda_telas_ped';
    protected $guarded = [];

    /**
     * Relación: Pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPed::class, 'prenda_ped_id');
    }

    /**
     * Relación: Tiene muchas fotos de tela
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(PrendaTalaFotoPed::class, 'prenda_tela_ped_id');
    }
}
