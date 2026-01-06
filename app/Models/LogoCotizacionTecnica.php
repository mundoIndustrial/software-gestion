<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogoCotizacionTecnica extends Model
{
    protected $table = 'logo_cotizacion_tecnicas';

    protected $fillable = [
        'logo_cotizacion_id',
        'tipo_logo_cotizacion_id',
        'observaciones_tecnica',
        'instrucciones_especiales',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación: Pertenece a un LogoCotizacion
     */
    public function logoCotizacion(): BelongsTo
    {
        return $this->belongsTo(LogoCotizacion::class);
    }

    /**
     * Relación: Pertenece a un TipoLogoCotizacion
     */
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoLogoCotizacion::class, 'tipo_logo_cotizacion_id');
    }

    /**
     * Relación: Una técnica puede tener múltiples prendas
     */
    public function prendas(): HasMany
    {
        return $this->hasMany(LogoCotizacionTecnicaPrenda::class)
            ->orderBy('orden');
    }

    /**
     * Scope: Solo registros activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener nombre de la técnica
     */
    public function getNombreTecnicaAttribute()
    {
        return $this->tipo->nombre ?? 'Desconocida';
    }

    /**
     * Obtener color de la técnica
     */
    public function getColorAttribute()
    {
        return $this->tipo->color ?? '#3498db';
    }
}
