<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PrendaVariantePed Model
 * 
 * Representa una variante de una prenda en un pedido
 * Guarda: tipo_manga_id, tipo_broche_boton_id, tiene_bolsillos, tiene_reflectivo y observaciones
 * Los colores y telas se guardan en PrendaPedidoColorTela
 */
class PrendaVariantePed extends Model
{
    protected $table = 'prenda_pedido_variantes';
    protected $guarded = [];
    protected $casts = [
        'telas_multiples' => 'json',
    ];

    /**
     * Relación: Pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Tiene muchos colores y telas
     */
    public function coloresTelas(): HasMany
    {
        return $this->hasMany(PrendaPedidoColorTela::class, 'prenda_pedido_id', 'prenda_pedido_id');
    }

    /**
     * Relación: Tipo de manga
     */
    public function tipoManga(): BelongsTo
    {
        return $this->belongsTo(TipoManga::class, 'tipo_manga_id');
    }

    /**
     * Relación: Tipo de broche
     */
    public function tipoBroche(): BelongsTo
    {
        // Usa la clase TipoBroche del archivo TipoBrocheBoton.php
        return $this->belongsTo('App\Models\TipoBrocheBoton', 'tipo_broche_boton_id');
    }
}
