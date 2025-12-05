<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialOrdenInsumo extends Model
{
    protected $table = 'materiales_orden_insumos';
    
    protected $fillable = [
        'numero_pedido',
        'nombre_material',
        'fecha_pedido',
        'fecha_llegada',
        'recibido',
    ];
    
    protected $casts = [
        'fecha_pedido' => 'datetime',
        'fecha_llegada' => 'datetime',
        'recibido' => 'boolean',
    ];
    
    /**
     * Relación con PedidoProduccion
     */
    public function orden()
    {
        return $this->belongsTo(PedidoProduccion::class, 'numero_pedido', 'numero_pedido');
    }
}
