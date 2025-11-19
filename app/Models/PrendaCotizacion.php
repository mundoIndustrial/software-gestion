<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrendaCotizacion extends Model
{
    protected $table = 'prendas_cotizacion';

    protected $fillable = [
        'cotizacion_id',
        'descripcion',
        'especificaciones',
        'imagen_url',
        'tallas',
        'aspectos_a_verificar',
        'forma_pago',
        'regimen',
        'filete_envio',
        'se_ha_vendido',
        'ultima_venta',
        'observacion',
        'estado'
    ];

    protected $casts = [
        'especificaciones' => 'array',
        'tallas' => 'array',
        'aspectos_a_verificar' => 'array',
        'ultima_venta' => 'decimal:2'
    ];

    /**
     * Relación con Cotizacion
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    /**
     * Relación con costos
     */
    public function costos(): HasMany
    {
        return $this->hasMany(CostoPrenda::class);
    }

    /**
     * Obtener total de costos de la prenda
     */
    public function getTotalCosto()
    {
        return $this->costos()->sum('costo');
    }
}
