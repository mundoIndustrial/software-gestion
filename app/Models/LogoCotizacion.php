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
        'ubicaciones',
        'observaciones_generales',
        'tipo_venta'
    ];

    protected $casts = [
        'imagenes' => 'array',
        'tecnicas' => 'array',
        'ubicaciones' => 'array',
        'observaciones_generales' => 'array'
    ];

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
