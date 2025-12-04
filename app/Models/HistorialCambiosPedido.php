<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialCambiosPedido extends Model
{
    public $timestamps = false;

    protected $table = 'historial_cambios_pedidos';

    protected $fillable = [
        'pedido_id',
        'estado_anterior',
        'estado_nuevo',
        'usuario_id',
        'usuario_nombre',
        'rol_usuario',
        'razon_cambio',
        'ip_address',
        'user_agent',
        'datos_adicionales',
        'created_at',
    ];

    protected $casts = [
        'datos_adicionales' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relación: Un historial pertenece a un pedido
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_id');
    }

    /**
     * Relación: Un historial pertenece a un usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
