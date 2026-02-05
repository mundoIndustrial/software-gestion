<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BodegaDetallesTalla extends Model
{
    use SoftDeletes;
    
    protected $table = 'bodega_detalles_talla';
    
    protected $fillable = [
        'pedido_produccion_id',
        'recibo_prenda_id',
        'numero_pedido',
        'talla',
        'prenda_nombre',
        'asesor',
        'empresa',
        'cantidad',
        'pendientes',
        'observaciones_bodega',
        'fecha_entrega',
        'estado_bodega',
        'area',
        'usuario_bodega_id',
        'usuario_bodega_nombre',
    ];
    
    protected $dates = [
        'fecha_entrega',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    
    // Relaciones
    public function pedidoProduccion()
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }
    
    public function reciboPrenda()
    {
        return $this->belongsTo(ReciboPrenda::class, 'recibo_prenda_id');
    }
}
