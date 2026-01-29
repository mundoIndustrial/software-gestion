<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogoCotizacionTecnicaPrendaFoto extends Model
{
    protected $table = 'logo_cotizacion_tecnica_prendas_fotos';

    protected $fillable = [
        'logo_cotizacion_tecnica_prenda_id',
        'ruta_original',
        'ruta_webp',
        'ruta_miniatura',
        'orden',
        'ancho',
        'alto',
        'tamaño',
    ];

    protected $casts = [
        'orden' => 'integer',
        'ancho' => 'integer',
        'alto' => 'integer',
        'tamaño' => 'integer',
    ];

    /**
     * Relación: Pertenece a una LogoCotizacionTecnicaPrenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(LogoCotizacionTecnicaPrenda::class, 'logo_cotizacion_tecnica_prenda_id');
    }

    /**
     * Accessor: Obtener URL de la imagen (preferencia: webp > original)
     */
    public function getUrlAttribute()
    {
        $ruta = $this->ruta_webp ?? $this->ruta_original;
        
        if (!$ruta) {
            return null;
        }
        
        // Si ya es una URL completa, retornarla
        if (str_starts_with($ruta, 'http')) {
            return $ruta;
        }
        
        // Si es una ruta relativa, construir la URL
        return str_starts_with($ruta, '/') ? '/storage' . $ruta : '/storage/' . $ruta;
    }

    /**
     * Accessor: Obtener URL de la miniatura
     */
    public function getUrlMiniaturaAttribute()
    {
        $ruta = $this->ruta_miniatura;
        
        if (!$ruta) {
            return $this->url; // Fallback a URL normal
        }
        
        if (str_starts_with($ruta, 'http')) {
            return $ruta;
        }
        
        return str_starts_with($ruta, '/') ? '/storage' . $ruta : '/storage/' . $ruta;
    }
}

