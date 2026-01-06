<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogoCotizacionTecnicaPrenda extends Model
{
    protected $table = 'logo_cotizacion_tecnica_prendas';

    protected $fillable = [
        'logo_cotizacion_id',
        'tipo_logo_id',
        'nombre_prenda',
        'descripcion',
        'ubicaciones',
        'tallas',
        'cantidad_general',
    ];

    protected $casts = [
        'ubicaciones' => 'array',
        'tallas' => 'array',
    ];

    /**
     * Relación: Pertenece a una LogoCotizacion
     */
    public function logoCotizacion(): BelongsTo
    {
        return $this->belongsTo(LogoCotizacion::class, 'logo_cotizacion_id');
    }

    /**
     * Relación: Pertenece a un TipoLogoCotizacion (técnica)
     */
    public function tipoLogo(): BelongsTo
    {
        return $this->belongsTo(TipoLogoCotizacion::class, 'tipo_logo_id');
    }
}
    public function getUbicacionesTextAttribute()
    {
        $ubicaciones = $this->ubicaciones ?? [];
        return is_array($ubicaciones) ? implode(', ', $ubicaciones) : $ubicaciones;
    }

    /**
     * Obtener tallas como string
     */
    public function getTallasTextAttribute()
    {
        $tallas = $this->tallas ?? [];
        return is_array($tallas) ? implode(', ', $tallas) : $tallas;
    }
}
