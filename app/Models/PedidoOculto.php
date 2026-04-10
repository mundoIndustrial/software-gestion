<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PedidoOculto extends Model
{
    use HasFactory;

    protected $table = 'pedido_ocultos';
    protected $fillable = ['pedido_id', 'user_id'];

    /**
     * Relación: El pedido oculto pertenece a un pedido
     */
    public function pedido()
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_id');
    }

    /**
     * Relación: El pedido oculto pertenece a un usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
