<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoObservacionesDespacho extends Model
{
    protected $table = 'pedido_observaciones_despacho';

    protected $fillable = [
        'pedido_produccion_id',
        'uuid',
        'contenido',
        'usuario_id',
        'usuario_nombre',
        'usuario_rol',
        'ip_address',
        'estado',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }
}
