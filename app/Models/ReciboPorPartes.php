<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReciboPorPartes extends Model
{
    protected $table = 'recibo_por_partes';

    protected $fillable = [
        'area',
        'encargado',
        'pedido_produccion_id',
        'prenda_pedido_id',
        'tipo_recibo',
        'consecutivo_original',
        'consecutivo_parcial',
    ];

    protected $casts = [
        'consecutivo_original' => 'decimal:2',
        'consecutivo_parcial' => 'decimal:2',
    ];

    /**
     * Relación: Pertenece a un pedido
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Tiene muchas tallas
     */
    public function tallas(): HasMany
    {
        return $this->hasMany(ReciboPorPartesTalla::class, 'recibo_por_partes_id');
    }
}
