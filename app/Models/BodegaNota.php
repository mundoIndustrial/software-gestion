<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodegaNota extends Model
{
    protected $table = 'bodega_notas';
    
    protected $fillable = [
        'pedido_produccion_id',
        'bodega_detalle_talla_id',
        'numero_pedido',
        'talla',
        'talla_color_id',
        'pedido_epp_id',
        'prenda_id',
        'row_hash',
        'contenido',
        'usuario_id',
        'usuario_nombre',
        'usuario_rol',
        'ip_address',
        'visto_at',
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * Relación con PedidoProduccion
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    /**
     * Relación con User
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
