<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrendaCotReflectivo extends Model
{
    protected $table = 'prenda_cot_reflectivo';

    protected $fillable = [
        'cotizacion_id',
        'prenda_cot_id',
        'variaciones',
        'ubicaciones',
        'descripcion',
    ];

    protected $casts = [
        'variaciones' => 'array',
        'ubicaciones' => 'array',
    ];

    /**
     * Relación: Una prenda_cot_reflectivo pertenece a una cotización
     */
    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    /**
     * Relación: Una prenda_cot_reflectivo pertenece a una prenda_cot
     */
    public function prendaCot()
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }
}
