<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodegaDetallesTalla extends Model
{
    use SoftDeletes;
    
    protected $table = 'bodega_detalles_talla';
    
    protected $fillable = [
        'pedido_produccion_id',
        'recibo_prenda_id',
        'numero_pedido',
        'talla',
        'prenda_nombre',
        'asesor',
        'empresa',
        'cantidad',
        'pendientes',
        'observaciones_bodega',
        'fecha_pedido',
        'fecha_entrega',
        'estado_bodega',
        'usuario_bodega_id',
        'usuario_bodega_nombre',
    ];
    
    protected $casts = [
        'fecha_pedido' => 'datetime',
        'fecha_entrega' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    // Relaciones
    public function pedidoProduccion(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }
    
    public function reciboPrenda(): BelongsTo
    {
        return $this->belongsTo(ReciboPrenda::class, 'recibo_prenda_id');
    }
    
    public function usuarioBodega(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_bodega_id');
    }
    
    // Scopes útiles
    public function scopePorPedido($query, $numeroPedido)
    {
        return $query->where('numero_pedido', $numeroPedido);
    }
    
    public function scopePorCliente($query, $cliente)
    {
        return $query->where('empresa', 'like', "%{$cliente}%");
    }
    
    public function scopeEntregados($query)
    {
        return $query->where('estado_bodega', 'Entregado');
    }
    
    public function scopePendientes($query)
    {
        return $query->where('estado_bodega', 'Pendiente');
    }
    
    public function scopePorArea($query, $area)
    {
        return $query->where('area', $area);
    }
    
    // Métodos de ayuda
    public function estaEntregado(): bool
    {
        return $this->estado_bodega === 'Entregado';
    }
    
    public function estaPendiente(): bool
    {
        return $this->estado_bodega === 'Pendiente';
    }
    
    public function getEstadoFormateado(): string
    {
        return match($this->estado_bodega) {
            'Pendiente' => 'Pendiente',
            'Entregado' => 'Entregado',
            'Anulado' => 'Anulado',
            'Homologar' => 'Por Homologar',
            default => $this->estado_bodega,
        };
    }
}
