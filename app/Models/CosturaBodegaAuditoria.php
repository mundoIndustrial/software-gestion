<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CosturaBodegaAuditoria extends Model
{
    use HasFactory;

    protected $table = 'costura_bodega_auditoria';

    protected $fillable = [
        'costura_bodega_detalle_id',
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
