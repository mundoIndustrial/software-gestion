<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use SoftDeletes;

    protected $table = 'pedidos_produccion';

    protected $fillable = [
        // Campos DDD
        'numero_pedido',
        'cliente_id',
        'estado',
        'novedades',
        
        // Campos heredados (compatible con existentes)
        'cotizacion_id',
        'numero_cotizacion',
        'cliente',
        'asesor_id',
        'forma_de_pago',
        'area',
        'fecha_ultimo_proceso',
        'fecha_de_creacion_de_orden',
        'dia_de_entrega',
        'fecha_estimada_de_entrega',
        'aprobado_por_supervisor_en',
        'cantidad_total',
    ];

    protected $casts = [
        'fecha_de_creacion_de_orden' => 'datetime',
        'fecha_estimada_de_entrega' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con los EPP del pedido
     */
    public function epps()
    {
        return $this->hasMany(PedidoEpp::class, 'pedido_produccion_id');
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

    /**
     * Relación con el asesor/usuario
     */
    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }
}
