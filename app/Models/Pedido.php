<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos_produccion';

    protected $fillable = [
        'numero',
        'cliente_id',
        'estado',
        'descripcion',
        'observaciones',
    ];

    /**
     * Relación con los EPP del pedido
     */
    public function epps()
    {
        return $this->hasMany(PedidoEpp::class);
    }

    /**
     * Relación con las prendas del pedido
     */
    public function prendas()
    {
        return $this->hasMany(PrendaPedido::class, 'pedido_produccion_id');
    }

    /**
     * Relación con el cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
