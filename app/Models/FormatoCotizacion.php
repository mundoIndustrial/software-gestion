<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormatoCotizacion extends Model
{
    protected $table = 'formatos_cotizacion';

    protected $fillable = [
        'cotizacion_id',
        'costos_por_prenda',
        'costo_total',
        'estado'
    ];

    protected $casts = [
        'costos_por_prenda' => 'array',
        'costo_total' => 'decimal:2'
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
