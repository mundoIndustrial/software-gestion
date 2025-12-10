<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaTela extends Model
{
    protected $table = 'prenda_telas_cotizacion';

    protected $fillable = [
        'variante_prenda_id',
        'color_id',
        'tela_id',
    ];

    /**
     * Relación con VariantePrenda
     */
    public function variantePrenda(): BelongsTo
    {
        return $this->belongsTo(VariantePrenda::class, 'variante_prenda_id');
    }

    /**
     * Relación con Color
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(ColorPrenda::class, 'color_id');
    }

    /**
     * Relación con Tela
     */
    public function tela(): BelongsTo
    {
        return $this->belongsTo(TelaPrenda::class, 'tela_id');
    }
}
