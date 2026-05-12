<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoParcial extends Model
{
    use SoftDeletes;

    protected $table = 'pedidos_parciales';

    protected $fillable = [
        'pedido_produccion_id',
        'prenda_pedido_id',
        'tipo_recibo',
        'estado',
        'consecutivo_actual',
        'consecutivo_inicial',
        'fecha_activacion',
        'activo',
        'notas',
        'ubicaciones',
        'observaciones',
        'datos_adicionales',
        'created_by',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_activacion' => 'datetime',
        'ubicaciones' => 'array',
        'datos_adicionales' => 'array',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    public function tallas()
    {
        return $this->hasMany(PedidoParcialTalla::class, 'pedido_parcial_id');
    }

    /**
     * Compatibilidad con ReciboPorPartes
     */
    public function getConsecutivoParcialAttribute()
    {
        return $this->consecutivo_actual;
    }

    /**
     * Compatibilidad con ReciboPorPartes
     */
    public function getConsecutivoOriginalAttribute()
    {
        return $this->consecutivo_inicial;
    }
}
