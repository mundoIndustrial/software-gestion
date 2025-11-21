<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCotizacion extends Model
{
    protected $table = 'tipos_cotizacion';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    /**
     * Relación con Cotizaciones
     */
    public function cotizaciones()
    {
        return $this->hasMany(Cotizacion::class, 'tipo_cotizacion_id');
    }
}
