<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntregaReciboCostura extends Model
{
    use HasFactory;

    protected $table = 'entrega_recibo_costura';

    protected $fillable = [
        'prenda_pedido_id',
        'consecutivo_recibo_id',
        'recibo_parcial_id',
        'encargado',
        'area',
        'cantidad_entregada',
        'detalle_tallas',
        'talla',
        'genero',
        'color_nombre',
        'usuario_id',
        'precio',
    ];

    protected $casts = [
        'cantidad_entregada' => 'integer',
        'detalle_tallas' => 'array',
    ];

    public function prendaPedido(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    public function consecutivoRecibo(): BelongsTo
    {
        return $this->belongsTo(ConsecutivoReciboPedido::class, 'consecutivo_recibo_id');
    }

    public function reciboParcial(): BelongsTo
    {
        return $this->belongsTo(ReciboPorPartes::class, 'recibo_parcial_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
