<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EppBodegaDetalle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'epp_bodega_detalles';

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
        'fecha_pedido',
        'fecha_entrega',
        'estado_bodega',
        'usuario_bodega_id',
        'usuario_bodega_nombre',
    ];

    protected $casts = [
        'fecha_pedido' => 'datetime',
        'fecha_entrega' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
