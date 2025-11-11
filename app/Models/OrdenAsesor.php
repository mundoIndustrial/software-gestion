<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenAsesor extends Model
{
    use HasFactory;

    protected $table = 'ordenes_asesores';

    protected $fillable = [
        'pedido',
        'numero_pedido_temporal',
        'numero_orden',
        'asesor_id',
        'cliente',
        'telefono',
        'email',
        'descripcion',
        'novedades',
        'forma_de_pago',
        'area',
        'monto_total',
        'cantidad_prendas',
        'estado',
        'estado_pedido',
        'es_borrador',
        'fecha_confirmacion',
        'prioridad',
        'fecha_entrega',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
        'fecha_confirmacion' => 'datetime',
        'monto_total' => 'decimal:2',
        'cantidad_prendas' => 'integer',
        'es_borrador' => 'boolean',
    ];

    /**
     * Relación con el asesor (usuario)
     */
    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /**
     * Relación con productos del pedido
     */
    public function productos()
    {
        return $this->hasMany(ProductoPedido::class, 'orden_asesor_id', 'id');
    }

    /**
     * Scope para filtrar por asesor
     */
    public function scopeDelAsesor($query, $asesorId)
    {
        return $query->where('asesor_id', $asesorId);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para órdenes del día
     */
    public function scopeDelDia($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope para órdenes del mes
     */
    public function scopeDelMes($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    /**
     * Scope para órdenes del año
     */
    public function scopeDelAnio($query)
    {
        return $query->whereYear('created_at', now()->year);
    }

    /**
     * Scope para borradores
     */
    public function scopeBorradores($query)
    {
        return $query->where('es_borrador', true);
    }

    /**
     * Scope para pedidos confirmados
     */
    public function scopeConfirmados($query)
    {
        return $query->where('es_borrador', false);
    }

    /**
     * Confirmar el pedido y asignar número consecutivo
     */
    public function confirmar()
    {
        if (!$this->es_borrador) {
            return false;
        }

        // Obtener el último número de pedido confirmado
        $ultimoPedido = self::where('es_borrador', false)
                           ->max('pedido') ?? 0;

        $this->pedido = $ultimoPedido + 1;
        $this->es_borrador = false;
        $this->estado_pedido = 'confirmado';
        $this->fecha_confirmacion = now();
        $this->save();

        return true;
    }

    /**
     * Verificar si es borrador
     */
    public function esBorrador()
    {
        return $this->es_borrador;
    }

    /**
     * Obtener el identificador para mostrar
     */
    public function getIdentificadorAttribute()
    {
        if ($this->es_borrador) {
            return 'BORRADOR-' . $this->id;
        }
        return 'PEDIDO-' . $this->pedido;
    }
}
