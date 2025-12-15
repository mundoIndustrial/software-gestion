<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PrendaPed Model
 * 
 * Representa una prenda en un pedido de producción
 * Equivalente a PrendaCot pero para pedidos
 */
class PrendaPed extends Model
{
    use SoftDeletes;

    protected $table = 'prendas_ped';
    protected $guarded = [];

    /**
     * Relación: Pertenece a un Pedido de Producción
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Tiene muchas fotos
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(PrendaFotoPed::class, 'prenda_ped_id');
    }

    /**
     * Relación: Tiene muchas telas
     */
    public function telas(): HasMany
    {
        return $this->hasMany(PrendaTelaPed::class, 'prenda_ped_id');
    }

    /**
     * Relación: Tiene muchas tallas
     */
    public function tallas(): HasMany
    {
        return $this->hasMany(PrendaTalaPed::class, 'prenda_ped_id');
    }

    /**
     * Relación: Tiene muchas variantes
     */
    public function variantes(): HasMany
    {
        return $this->hasMany(PrendaVariantePed::class, 'prenda_ped_id');
    }
}
