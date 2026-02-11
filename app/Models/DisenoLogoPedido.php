<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisenoLogoPedido extends Model
{
    protected $table = 'disenos_logo_pedido';

    protected $fillable = [
        'proceso_prenda_detalle_id',
        'url',
    ];

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(PedidosProcesosPrendaDetalle::class, 'proceso_prenda_detalle_id');
    }
}
