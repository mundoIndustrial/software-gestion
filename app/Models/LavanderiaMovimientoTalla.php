<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LavanderiaMovimientoTalla extends Model
{
    protected $table = 'lavanderia_movimiento_tallas';

    protected $fillable = [
        'lavanderia_movimiento_id',
        'prenda_id',
        'prenda_bodega_id',
        'tipo_prenda',
        'talla',
        'genero',
        'color',
        'cantidad_enviada',
        'cantidad_recibida',
    ];

    protected $casts = [
        'cantidad_enviada' => 'integer',
        'cantidad_recibida' => 'integer',
    ];

    /**
     * Relación: Una talla pertenece a un movimiento
     */
    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(LavanderiaMovimiento::class, 'lavanderia_movimiento_id');
    }

    /**
     * Relación: Una talla pertenece a una prenda (COSTURA)
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(Prenda::class, 'prenda_id');
    }

    /**
     * Relación: Una talla pertenece a una prenda de bodega (BODEGA)
     */
    public function prendaBodega(): BelongsTo
    {
        return $this->belongsTo(PrendaBodega::class, 'prenda_bodega_id');
    }

    /**
     * Obtiene el nombre de la prenda según el tipo
     */
    public function getPrendaNombre(): string
    {
        if ($this->tipo_prenda === 'BODEGA' && $this->prendaBodega) {
            return $this->prendaBodega->nombre ?? 'Sin prenda';
        }
        
        if ($this->tipo_prenda === 'COSTURA' && $this->prenda) {
            return $this->prenda->nombre_prenda ?? 'Sin prenda';
        }
        
        return 'Sin prenda';
    }
}
