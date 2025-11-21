<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialCotizacion extends Model
{
    protected $table = 'historial_cotizaciones';

    protected $fillable = [
        'cotizacion_id',
        'tipo_cambio',
        'descripcion',
        'datos_anteriores',
        'datos_nuevos',
        'usuario_id',
        'usuario_nombre',
        'ip_address'
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'created_at' => 'datetime'
    ];

    /**
     * Relación con Cotizacion
     */
    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
