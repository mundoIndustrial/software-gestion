<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model: PedidosProcessImagenes
 * 
 * Imágenes asociadas a procesos productivos
 */
class PedidosProcessImagenes extends Model
{
    use SoftDeletes;

    protected $table = 'pedidos_procesos_imagenes';

    protected $fillable = [
        'proceso_prenda_detalle_id',
        'ruta_original',
        'ruta_webp',
        'orden',
        'es_principal',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['url'];

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(PedidosProcesosPrendaDetalle::class, 'proceso_prenda_detalle_id');
    }

    /**
     * Accessor para obtener la URL completa de la imagen
     * Soporta tanto rutas relativas (storage/...) como URLs completas (http://...)
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
        
        // Si comienza con 'storage/', agregar el /
        if (str_starts_with($ruta, 'storage/')) {
            return '/' . $ruta;
        }
        
        // Si es una ruta relativa, agregar /storage/
        return '/storage/' . ltrim($ruta, '/');
    }
}
