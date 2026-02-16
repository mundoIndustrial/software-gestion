<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrendaEntrega extends Model
{
    use HasFactory;

    protected $table = 'prenda_entregas';

    protected $fillable = [
        'prenda_pedido_id',
        'entregado',
        'fecha_entrega',
        'usuario_id',
    ];

    protected $casts = [
        'entregado' => 'boolean',
        'fecha_entrega' => 'datetime',
    ];

    /**
     * Relación con la prenda del pedido
     */
    public function prendaPedido()
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación con el usuario que marcó como entregado
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
