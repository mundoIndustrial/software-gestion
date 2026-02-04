<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrendaFotoCot extends Model
{
    protected $table = 'prenda_fotos_cot';

    protected $fillable = [
        'prenda_cot_id',
        'ruta_original',
        'ruta_webp',
        'ruta_miniatura',
        'orden',
        'ancho',
        'alto',
        'tamaño',
    ];

    /**
     * Incluir el accessor 'url' cuando se convierte a array/json
     */
    protected $appends = ['url'];

    /**
     * Relación: Una foto pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
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
        // Si ya comienza con /storage/, devolverla tal cual
        if (str_starts_with($ruta, '/storage/')) {
            return $ruta;
        }
        // Si comienza con 'storage/', convertir a /storage/
        if (str_starts_with($ruta, 'storage/')) {
            return '/' . $ruta;
        }
        // Si es una ruta relativa, agregar /storage/
        return '/storage/' . ltrim($ruta, '/');
    }
}
