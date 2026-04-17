<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialesOrdenInsumos extends Model
{
    protected $table = 'materiales_orden_insumos';

    protected $fillable = [
        'nombre_material',
        'fecha_pedido',
        'fecha_llegada',
        'recibido',
        'numero_pedido',
        'numero_recibo',
        'prenda_id',
        'fecha_orden',
        'fecha_pago',
        'fecha_despacho',
        'observaciones',
        'dias_demora',
        'marcado',
    ];

    protected $casts = [
        'fecha_pedido' => 'datetime',
        'fecha_llegada' => 'datetime',
        'fecha_orden' => 'datetime',
        'fecha_pago' => 'datetime',
        'fecha_despacho' => 'datetime',
        'recibido' => 'boolean',
        'marcado' => 'boolean',
    ];

    protected $appends = ['dias_demora'];

    public function pedido()
    {
        return $this->belongsTo(PedidoProduccion::class, 'numero_pedido', 'numero_pedido');
    }

    public function prenda()
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_id');
    }

    /**
     * Diferencia entre fecha_llegada y fecha_pedido:
     * excluye fines de semana y festivos colombianos.
     */
    public function getDiasDemoraAttribute()
    {
        if (!$this->fecha_pedido || !$this->fecha_llegada) {
            return null;
        }

        $inicio = $this->fecha_pedido->copy()->startOfDay();
        $fin = $this->fecha_llegada->copy()->startOfDay();

        if ($fin->lt($inicio)) {
            return 0;
        }

        $diasHabiles = 0;
        $fecha = $inicio->copy();

        while ($fecha->lte($fin)) {
            if ($fecha->isBusinessDay()) {
                $diasHabiles++;
            }
            $fecha->addDay();
        }

        // No contar el día de inicio
        return max(0, $diasHabiles - 1);
    }
}

