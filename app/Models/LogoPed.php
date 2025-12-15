<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LogoPed Model
 * 
 * Representa un logo en un pedido de producción
 * Equivalente a LogoCotizacion pero para pedidos
 */
class LogoPed extends Model
{
    use SoftDeletes;

    protected $table = 'logo_ped';
    protected $guarded = [];
    protected $casts = [
        'observaciones_generales' => 'json',
    ];

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
        return $this->hasMany(LogoFotoPed::class, 'logo_ped_id');
    }
}
