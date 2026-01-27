<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogoCotizacionTecnica extends Model
{
    protected $table = 'logo_cotizacion_tecnica_prendas';

    protected $fillable = [
        'logo_cotizacion_id',
        'tipo_logo_id',
        'prenda_cot_id',
        'observaciones',
        'ubicaciones',
        'talla_cantidad',
        'variaciones_prenda',
        'grupo_combinado',
    ];

    protected $casts = [
        'ubicaciones' => 'array',
        'talla_cantidad' => 'array',
        'variaciones_prenda' => 'array',
    ];

    /**
     * Relación: Pertenece a un LogoCotizacion
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

    /**
     * Relación: Pertenece a una PrendaCot
     */
    public function prendaCot(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }

    /**
     * Relación: Tiene muchas fotos
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(LogoCotizacionTecnicaPrendaFoto::class, 'logo_cotizacion_tecnica_prenda_id');
    }

    /**
     * Obtener ubicaciones como string
     */
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
        $tallas = $this->talla_cantidad ?? [];
        return is_array($tallas) ? implode(', ', array_keys($tallas)) : $tallas;
    }
}
