<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogoFotoCot extends Model
{
    use HasFactory;

    protected $table = 'logo_fotos_cot';

    protected $fillable = [
        'logo_cotizacion_id',
        'ruta_original',
        'ruta_webp',
        'ruta_miniatura',
        'orden',
        'ancho',
        'alto',
        'tamaño',
    ];

    protected $casts = [
        'logo_cotizacion_id' => 'integer',
        'orden' => 'integer',
        'ancho' => 'integer',
        'alto' => 'integer',
        'tamaño' => 'integer',
    ];

    /**
     * Incluir el accessor 'url' cuando se convierte a array/json
     */
    protected $appends = ['url'];

    /**
     * Relación: Una foto de logo pertenece a una cotización de logo
     */
    public function logoCotizacion()
    {
        return $this->belongsTo(LogoCotizacion::class, 'logo_cotizacion_id');
    }

    /**
     * Accessor: Obtener URL de la imagen (usa WebP si existe, sino original)
     * Las rutas se guardan sin prefijo 'storage/', así que aquí se agrega
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
        // Si es una ruta relativa, agregar /storage/
        return '/storage/' . ltrim($ruta, '/');
    }
}
