<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EppBodegaAuditoria extends Model
{
    use HasFactory;

    protected $table = 'epp_bodega_auditoria';

    protected $fillable = [
        'epp_bodega_detalle_id',
        'numero_pedido',
        'talla',
        'prenda_nombre',
        'estado_anterior',
        'estado_nuevo',
        'usuario_id',
        'usuario_nombre',
        'descripcion_cambio',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
