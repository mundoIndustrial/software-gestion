<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\PedidoProduccion;
use App\Models\User;

class EntregaPrenda extends Model
{
    protected $table = 'entregas_prendas';
    
    protected $fillable = [
        'pedido_produccion_id',
        'numero_pedido',
        'prenda_nombre',
        'talla',
        'cantidad',
        'cliente',
        'asesor',
        'fecha_entrega',
        'hora_entrega',
        'usuario_entrega_id',
        'usuario_entrega_nombre',
        'observaciones_entrega',
    ];
    
    protected $casts = [
        'fecha_entrega' => 'datetime',
        'hora_entrega' => 'time',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // Relaciones
    public function pedidoProduccion()
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }
    
    public function usuarioEntrega()
    {
        return $this->belongsTo(User::class, 'usuario_entrega_id');
    }
    
    // Scopes útiles
    public function scopePorPedido($query, $numeroPedido)
    {
        return $query->where('numero_pedido', $numeroPedido);
    }
    
    public function scopePorCliente($query, $cliente)
    {
        return $query->where('cliente', 'LIKE', "%{$cliente}%");
    }
    
    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha_entrega', $fecha);
    }
    
    // Métodos de conveniencia
    public function getFechaHoraEntregaAttribute()
    {
        if ($this->fecha_entrega && $this->hora_entrega) {
            return $this->fecha_entrega->format('Y-m-d') . ' ' . $this->hora_entrega->format('H:i');
        }
        
        return $this->fecha_entrega ? $this->fecha_entrega->format('Y-m-d') : null;
    }
    
    public function getEstadoPedido()
    {
        return $this->pedidoProduccion ? $this->pedidoProduccion->estado : null;
    }
    
    public function getNombreCompleto()
    {
        return $this->prenda_nombre . ' - ' . $this->talla . ' (' . $this->cantidad . ' unidades)';
    }
}
