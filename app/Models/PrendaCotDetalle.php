<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaCotDetalle extends Model
{
    protected $table = 'prenda_cot_detalles';

    protected $fillable = [
        'prenda_cot_id',
        'disponibilidad',
        'ultima_venta',
    ];

    /**
     * Relación: Un detalle pertenece a una prenda de cotización
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }
}
