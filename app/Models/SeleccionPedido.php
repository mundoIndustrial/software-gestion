<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SeleccionPedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id',
        'user_id',
        'seleccionado',
    ];

    protected $casts = [
        'seleccionado' => 'boolean',
    ];

    // Relaciones
    public function pedido()
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope para obtener selecciones de un usuario
    public function scopeParaUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Scope para obtener selecciones activas
    public function scopeSeleccionadas($query)
    {
        return $query->where('seleccionado', true);
    }
}
