<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MaterialesOrdenInsumos extends Model
{
    protected $table = 'materiales_orden_insumos';

    protected $fillable = [
        'nombre_material',
        'fecha_pedido',
        'fecha_llegada',
        'recibido',
        'tabla_original_pedido',
    ];

    protected $casts = [
        'fecha_pedido' => 'date',
        'fecha_llegada' => 'date',
        'recibido' => 'boolean',
    ];

    protected $appends = ['dias_demora'];

    /**
     * Relación con la orden original
     */
    public function orden()
    {
        return $this->belongsTo(TablaOriginal::class, 'tabla_original_pedido', 'pedido');
    }

    /**
     * Calcular días de demora automáticamente
     * Diferencia entre fecha_llegada y fecha_pedido
     */
    public function getDiasDemoraAttribute()
    {
        if ($this->fecha_pedido && $this->fecha_llegada) {
            return $this->fecha_llegada->diffInDays($this->fecha_pedido);
        }
        return null;
    }
}
