<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReflectivoCotizacionFoto extends Model
{
    use SoftDeletes;

    protected $table = 'reflectivo_fotos_cotizacion';

    protected $fillable = [
        'reflectivo_cotizacion_id',
        'ruta_original',
        'ruta_webp',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    /**
     * Incluir el accessor 'url' cuando se convierte a array/json
     */
    protected $appends = ['url'];

    /**
     * Relación: Una foto pertenece a un reflectivo_cotizacion
     */
    public function reflectivoCotizacion(): BelongsTo
    {
        return $this->belongsTo(ReflectivoCotizacion::class);
    }

    /**
     * Accessor: Obtener URL de la imagen (usa WebP si existe, sino original)
     * Las rutas se guardan sin prefijo 'storage/', así que aquí se agrega
     * Maneja tanto rutas antiguas (cotizaciones/reflectivo/) como nuevas (cotizaciones/{id}/reflectivo/)
     */
    public function getUrlAttribute(): string
    {
        $ruta = $this->ruta_webp ?? $this->ruta_original;
        if (!$ruta) {
            return '';
        }
        // Si ya es una URL completa, devolverla tal cual
        if (str_starts_with($ruta, 'http')) {
            return $ruta;
        }
        // Si ya comienza con /storage/, es accesible
        if (str_starts_with($ruta, '/storage/')) {
            return $ruta;
        }
        // Si comienza con 'storage/', agregable /
        if (str_starts_with($ruta, 'storage/')) {
            return '/' . $ruta;
        }
        
        //  Manejo de rutas antiguas y nuevas
        // Si empieza con cotizaciones/ (antigua o nueva), agregar /storage/
        if (str_starts_with($ruta, 'cotizaciones/')) {
            return '/storage/' . ltrim($ruta, '/');
        }
        
        // Si es una ruta relativa, agregar /storage/
        return '/storage/' . ltrim($ruta, '/');
    }
}
