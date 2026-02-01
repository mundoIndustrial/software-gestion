<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrendaTelaCot extends Model
{
    protected $table = 'prenda_telas_cot';

    protected $fillable = [
        'prenda_cot_id',
        'variante_prenda_cot_id',
        'color_id',
        'tela_id',
    ];

    /**
     * Relación: Una tela pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }

    /**
     * Relación: Una tela pertenece a una variante
     */
    public function variante(): BelongsTo
    {
        return $this->belongsTo(PrendaVarianteCot::class, 'variante_prenda_cot_id');
    }

    /**
     * Relación: Una tela tiene un color
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(ColorPrenda::class, 'color_id');
    }

    /**
     * Relación: Una tela tiene un tipo de tela
     */
    public function tela(): BelongsTo
    {
        return $this->belongsTo(TelaPrenda::class, 'tela_id');
    }

    /**
     * Relación: Una prenda-tela tiene muchas fotos
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(PrendaTelaFotoCot::class, 'prenda_tela_cot_id');
    }
}
