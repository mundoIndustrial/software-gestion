<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisenoLogoPedido extends Model
{
    protected $table = 'disenos_logo_pedido';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'proceso_prenda_detalle_id',
        'url',
        'estado',
        'revisada',
    ];

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(PedidosProcesosPrendaDetalle::class, 'proceso_prenda_detalle_id');
    }

    public function novedades(): HasMany
    {
        return $this->hasMany(DisenoLogoPedidoNovedad::class, 'diseno_logo_pedido_id')->latest();
    }
}
