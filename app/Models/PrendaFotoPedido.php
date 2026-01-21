<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PrendaFotoPedido Model
 * 
 * Representa una foto de prenda en un pedido
 * Equivalente a PrendaFotoCot pero para pedidos
 */
class PrendaFotoPedido extends Model
{
    use SoftDeletes;

    protected $table = 'prenda_fotos_pedido';
    protected $guarded = [];

    protected $appends = ['url'];

    /**
     * Relación: Pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Accessor: Obtener URL de la imagen (usa WebP si existe, sino original)
     * Retorna rutas SIN /storage/ al inicio para evitar duplicación
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
        
        // SIEMPRE remover /storage/ del inicio si existe
        // Esto asegura que nunca retornamos /storage/storage/...
        while (str_starts_with($ruta, '/storage/')) {
            $ruta = ltrim($ruta, '/');
        }
        
        // Si comienza con /, remover el / inicial
        if (str_starts_with($ruta, '/')) {
            $ruta = ltrim($ruta, '/');
        }
        
        // Retornar sin /storage/ al inicio
        // El frontend o el servidor agregará /storage/ según sea necesario
        return $ruta;
    }
}
