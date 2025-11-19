<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoProduccion extends Model
{
    use SoftDeletes;

    protected $table = 'pedidos_produccion';

    protected $fillable = [
        'cotizacion_id',
        'numero_pedido',
        'cliente',
        'novedades',
        'asesora',
        'forma_de_pago',
        'estado',
        'fecha_de_creacion_de_orden',
        'dia_de_entrega',
        'fecha_estimada_de_entrega',
    ];

    protected $casts = [
        'fecha_de_creacion_de_orden' => 'date',
        'fecha_estimada_de_entrega' => 'date',
    ];

    /**
     * Relación: Un pedido pertenece a una cotización
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    /**
     * Relación: Un pedido tiene muchas prendas
     */
    public function prendas(): HasMany
    {
        return $this->hasMany(PrendaPedido::class);
    }
}
