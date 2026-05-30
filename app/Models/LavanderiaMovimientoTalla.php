<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LavanderiaMovimientoTalla extends Model
{
    protected $table = 'lavanderia_movimiento_tallas';

    protected $fillable = [
        'lavanderia_movimiento_id',
        'lavanderia_movimiento_recibo_id',
        'prenda_id',
        'prenda_bodega_id',
        'prenda_agregada_id',
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
     * Relación: Una talla pertenece a un recibo de movimiento
     */
    public function reciboMovimiento(): BelongsTo
    {
        return $this->belongsTo(LavanderiaMovimientoRecibo::class, 'lavanderia_movimiento_recibo_id');
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
     * Relación: Una talla pertenece a una prenda agregada manualmente
     */
    public function prendaAgregada(): BelongsTo
    {
        return $this->belongsTo(LavanderiaPrendaAgregada::class, 'prenda_agregada_id');
    }

    /**
     * Obtiene el nombre de la prenda según el tipo
     */
    public function getPrendaNombre(): string
    {
        if ($this->prenda_bodega_id && $this->prendaBodega) {
            return $this->prendaBodega->nombre ?? 'Sin prenda';
        }
        
        if ($this->prenda_id && $this->prenda) {
            return $this->prenda->nombre_prenda ?? 'Sin prenda';
        }

        if ($this->prenda_agregada_id && $this->prendaAgregada) {
            return $this->prendaAgregada->descripcion ?? 'Sin prenda';
        }
        
        return 'Sin prenda';
    }
}
