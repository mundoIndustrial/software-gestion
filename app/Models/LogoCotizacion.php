<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogoCotizacion extends Model
{
    protected $table = 'logo_cotizaciones';

    protected $fillable = [
        'cotizacion_id',
        'descripcion',
        'imagenes',
        'tecnicas',
        'observaciones_tecnicas',
        'secciones',
        'observaciones_generales',
        'tipo_venta'
    ];

    protected $casts = [
        'imagenes' => 'array',
        'tecnicas' => 'array',
        'secciones' => 'array',
        'observaciones_generales' => 'array'
    ];

    /**
     * Accessor para compatibilidad: ubicaciones retorna secciones
     */
    public function getUbicacionesAttribute()
    {
        return $this->secciones ?? [];
    }

    /**
     * Relación con Cotizacion
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    /**
     * Relación: Un logo puede tener múltiples fotos (máximo 5)
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(LogoFotoCot::class, 'logo_cotizacion_id')->orderBy('orden');
    }
}
