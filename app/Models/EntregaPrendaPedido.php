<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntregaPrendaPedido extends Model
{
    use SoftDeletes;

    protected $table = 'entrega_prenda_pedido';

    protected $fillable = [
        'prenda_pedido_id',
        'talla',
        'cantidad_original',
        'costurero',
        'total_producido_por_talla',
        'total_pendiente_por_talla',
        'fecha_completado',
    ];

    protected $casts = [
        'fecha_completado' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece a una PrendaPedido
     */
    public function prendaPedido(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación: Obtener el pedido de producción a través de la prenda (HasOneThrough)
     */
    public function pedido()
    {
        return $this->hasOneThrough(
            PedidoProduccion::class,
            PrendaPedido::class,
            'id',
            'id',
            'prenda_pedido_id',
            'pedido_produccion_id'
        );
    }

    /**
     * Calcular total pendiente automáticamente
     */
    public function calcularTotalPendiente()
    {
        $this->total_pendiente_por_talla = max(0, $this->cantidad_original - $this->total_producido_por_talla);
        return $this;
    }

    /**
     * Actualizar total producido y recalcular pendiente
     */
    public function actualizarProduccion($cantidadProducida)
    {
        $this->total_producido_por_talla = $cantidadProducida;
        $this->calcularTotalPendiente();
        
        // Si se completó toda la cantidad, registrar fecha
        if ($this->total_pendiente_por_talla == 0) {
            $this->fecha_completado = now();
        } else {
            $this->fecha_completado = null;
        }
        
        return $this;
    }

    /**
     * Scope: Filtrar por prenda_pedido
     */
    public function scopeByPrendaPedido($query, $prendaPedidoId)
    {
        return $query->where('prenda_pedido_id', $prendaPedidoId);
    }

    /**
     * Scope: Filtrar por costurero
     */
    public function scopeByCosturero($query, $costurero)
    {
        return $query->where('costurero', $costurero);
    }

    /**
     * Scope: Filtrar por talla
     */
    public function scopeByTalla($query, $talla)
    {
        return $query->where('talla', $talla);
    }

    /**
     * Scope: Filtrar pendientes (total_pendiente_por_talla > 0)
     */
    public function scopePendientes($query)
    {
        return $query->where('total_pendiente_por_talla', '>', 0);
    }

    /**
     * Scope: Filtrar completados (total_pendiente_por_talla = 0)
     */
    public function scopeCompletados($query)
    {
        return $query->where('total_pendiente_por_talla', 0);
    }
}
