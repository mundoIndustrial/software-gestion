<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TallasCostosCot extends Model
{
    protected $table = 'tallas_costos_cot';

    protected $fillable = [
        'cotizacion_id',
        'prenda_cot_id',
        'descripcion',
    ];

    /**
     * Relación: Pertenece a una Cotización
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    /**
     * Relación: Pertenece a una Prenda de Cotización
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }
}
