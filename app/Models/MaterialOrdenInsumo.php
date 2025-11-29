<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialOrdenInsumo extends Model
{
    protected $table = 'materiales_orden_insumos';
    
    protected $fillable = [
        'tabla_original_pedido',
        'nombre_material',
        'fecha_pedido',
        'fecha_llegada',
        'recibido',
    ];
    
    protected $casts = [
        'fecha_pedido' => 'date',
        'fecha_llegada' => 'date',
        'recibido' => 'boolean',
    ];
    
    /**
     * Relación con TablaOriginal
     */
    public function orden()
    {
        return $this->belongsTo(TablaOriginal::class, 'tabla_original_pedido', 'pedido');
    }
}
