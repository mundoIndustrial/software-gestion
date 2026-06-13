<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DespachoComprobante extends Model
{
    protected $table = 'despacho_comprobantes';

    protected $fillable = [
        'pedido_produccion_id',
        'usuario_id',
        'numero_pedido',
        'cliente_nombre',
        'cliente_email',
        'comp_factura_no',
        'fecha_entrega',
        'observaciones',
        'snapshot',
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
        'snapshot' => 'array',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function filas(): HasMany
    {
        return $this->hasMany(DespachoComprobanteFila::class, 'despacho_comprobante_id');
    }
}
