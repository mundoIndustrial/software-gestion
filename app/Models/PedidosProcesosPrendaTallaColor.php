<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PedidosProcesosPrendaTallaColor Model
 * 
 * Representa la desglose de colores para cada talla en un proceso.
 * Permite rastrear qué colores se procesan en cada talla de cada proceso.
 * 
 * @property int $id
 * @property int $pedidos_procesos_prenda_talla_id
 * @property string $color_nombre
 * @property string $tela_nombre
 * @property int $cantidad
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PedidosProcesosPrendaTallaColor extends Model
{
    protected $table = 'pedidos_procesos_prenda_talla_colores';

    protected $fillable = [
        'pedidos_procesos_prenda_talla_id',
        'color_nombre',
        'tela_nombre',
        'cantidad',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    /**
     * Relación con PedidosProcesosPrendaTalla
     */
    public function tallaProcesoDetalle(): BelongsTo
    {
        return $this->belongsTo(PedidosProcesosPrendaTalla::class, 'pedidos_procesos_prenda_talla_id');
    }
}
