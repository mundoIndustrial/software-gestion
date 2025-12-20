<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ProcesosPedidosLogo
 * 
 * Registra el historial de áreas/estados por las que pasa un pedido de logo
 */
class ProcesosPedidosLogo extends Model
{
    protected $table = 'procesos_pedidos_logo';

    protected $fillable = [
        'logo_pedido_id',
        'area',
        'observaciones',
        'fecha_entrada',
        'usuario_id',
    ];

    protected $casts = [
        'fecha_entrada' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    /**
     * Relación con LogoPedido
     */
    public function logoPedido()
    {
        return $this->belongsTo(LogoPedido::class, 'logo_pedido_id');
    }

    /**
     * Relación con User (quien registró el cambio)
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    /**
     * Obtener el área actual de un pedido
     */
    public function scopeAreaActual($query, $logoPedidoId)
    {
        return $query->where('logo_pedido_id', $logoPedidoId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Obtener todas las áreas por las que ha pasado
     */
    public function scopeHistorial($query, $logoPedidoId)
    {
        return $query->where('logo_pedido_id', $logoPedidoId)
            ->orderBy('created_at', 'asc');
    }

    // =============================================
    // MÉTODOS ESTÁTICOS
    // =============================================

    /**
     * Crear un nuevo proceso cuando se crea un pedido logo
     */
    public static function crearProcesoInicial($logoPedidoId, $usuarioId = null)
    {
        return static::create([
            'logo_pedido_id' => $logoPedidoId,
            'area' => 'Creacion de orden',
            'fecha_entrada' => now(),
            'usuario_id' => $usuarioId ?? auth()?->id(),
            'observaciones' => 'Pedido creado',
        ]);
    }

    /**
     * Cambiar el área de un pedido
     */
    public static function cambiarArea($logoPedidoId, $nuevaArea, $observaciones = null, $usuarioId = null)
    {
        return static::create([
            'logo_pedido_id' => $logoPedidoId,
            'area' => $nuevaArea,
            'fecha_entrada' => now(),
            'usuario_id' => $usuarioId ?? auth()?->id(),
            'observaciones' => $observaciones,
        ]);
    }

    /**
     * Obtener el área actual de un pedido
     */
    public static function obtenerAreaActual($logoPedidoId)
    {
        return static::where('logo_pedido_id', $logoPedidoId)
            ->orderBy('created_at', 'desc')
            ->first()?->area ?? 'Creacion de orden';
    }
}
