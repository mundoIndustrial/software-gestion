<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NovedadEntrega extends Model
{
    protected $table = 'novedades_entregas';

    protected $fillable = [
        'prenda_pedido_id',
        'prenda_bodega_id',
        'consecutivo_recibo_id',
        'recibo_parcial_id',
        'usuario_id',
        'encargado',
        'observaciones',
        'area',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function recibo()
    {
        return $this->belongsTo(ConsecutivoReciboPedido::class, 'consecutivo_recibo_id');
    }

    public function reciboParcial()
    {
        return $this->belongsTo(ReciboPorPartes::class, 'recibo_parcial_id');
    }

    public function prenda()
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }
}

