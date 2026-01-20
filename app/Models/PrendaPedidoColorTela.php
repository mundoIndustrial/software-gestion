<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrendaPedidoColorTela extends Model
{
    protected $table = 'prenda_pedido_colores_telas';

    protected $fillable = [
        'prenda_pedido_id',
        'color_id',
        'tela_id',
    ];

    /**
     * Relación con PrendaPedido
     */
    public function prendaPedido(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación con ColorPrenda
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(ColorPrenda::class, 'color_id');
    }

    /**
     * Relación con TelaPrenda
     */
    public function tela(): BelongsTo
    {
        return $this->belongsTo(TelaPrenda::class, 'tela_id');
    }

    /**
     * Relación con PrendaFotoTelaPedido
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(PrendaFotoTelaPedido::class, 'prenda_pedido_colores_telas_id');
    }
}
