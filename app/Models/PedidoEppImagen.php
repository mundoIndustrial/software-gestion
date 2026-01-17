<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoEppImagen extends Model
{
    protected $table = 'pedido_epp_imagenes';

    protected $fillable = [
        'pedido_epp_id',
        'archivo',
        'principal',
        'orden',
    ];

    protected $casts = [
        'principal' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Relación con el EPP del pedido
     */
    public function pedidoEpp()
    {
        return $this->belongsTo(PedidoEpp::class);
    }
}
