<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LogoCotizacionTelasPrenda
 * 
 * Modelo para gestionar la información de Color, Tela y Referencia
 * de cada prenda en una cotización de logo INDIVIDUAL
 * 
 * Tabla: logo_cotizacion_telas_prenda
 */
class LogoCotizacionTelasPrenda extends Model
{
    protected $table = 'logo_cotizacion_telas_prenda';

    protected $fillable = [
        'logo_cotizacion_id',
        'prenda_cot_id',
        'tela',
        'color',
        'ref',
        'img',
    ];

    protected $casts = [
        'logo_cotizacion_id' => 'integer',
        'prenda_cot_id' => 'integer',
    ];

    /**
     * Relación: Pertenece a una LogoCotizacion
     */
    public function logoCotizacion(): BelongsTo
    {
        return $this->belongsTo(LogoCotizacion::class, 'logo_cotizacion_id');
    }

    /**
     * Relación: Pertenece a una Prenda de Cotización
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }

    /**
     * Obtener la URL pública de la imagen de la tela
     * 
     * @return string|null
     */
    public function getUrlImagenAttribute(): ?string
    {
        if (!$this->img) {
            return null;
        }

        // Si la ruta no comienza con http, construir la URL pública
        if (!str_starts_with($this->img, 'http')) {
            return asset('storage/' . str_replace('storage/app/public/', '', $this->img));
        }

        return $this->img;
    }
}
