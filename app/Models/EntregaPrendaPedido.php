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
        'numero_pedido',
        'nombre_prenda',
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
     * Relación: Pertenece a un PedidoProduccion (via numero_pedido)
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'numero_pedido', 'numero_pedido');
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
     * Scope: Filtrar por numero_pedido
     */
    public function scopeByNumeroPedido($query, $numeroPedido)
    {
        return $query->where('numero_pedido', $numeroPedido);
    }

    /**
     * Scope: Filtrar por nombre_prenda
     */
    public function scopeByNombrePrenda($query, $nombrePrenda)
    {
        return $query->where('nombre_prenda', $nombrePrenda);
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
