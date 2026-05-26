<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoAnchoGeneral extends Model
{
    protected $table = 'pedido_ancho_general';

    protected $fillable = [
        'pedido_produccion_id',
        'prenda_pedido_id',
        'prenda_bodega_id',
        'numero_recibo',
        'ancho',
        'metraje',
        'tipo_modo',
        'contenido_mano',
        'observaciones',
        'creado_por',
        'actualizado_por'
    ];

    protected $casts = [
        'ancho' => 'decimal:2',
        'metraje' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relación con PedidoProduccion
     */
    public function pedidoProduccion()
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    /**
     * Relación con PrendaPedido
     */
    public function prendaPedido()
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Obtener el usuario que creó el registro
     */
    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Obtener el usuario que actualizó el registro
     */
    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
