<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LavanderiaMovimientoTalla extends Model
{
    protected $table = 'lavanderia_movimiento_tallas';

    protected $fillable = [
        'lavanderia_movimiento_id',
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
}
