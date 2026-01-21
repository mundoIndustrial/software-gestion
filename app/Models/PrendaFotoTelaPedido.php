<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PrendaFotoTelaPedido Model
 * 
 * Representa una foto de tela en un pedido
 * Equivalente a PrendaTelaFotoCot pero para pedidos
 */
class PrendaFotoTelaPedido extends Model
{
    use SoftDeletes;

    protected $table = 'prenda_fotos_tela_pedido';
    protected $guarded = [];

    protected $appends = ['url'];

    /**
     * Relación: Pertenece a una combinación de color-tela
     */
    public function colorTela(): BelongsTo
    {
        return $this->belongsTo(PrendaPedidoColorTela::class, 'prenda_pedido_colores_telas_id');
    }

    /**
     * Accessor: Obtener URL de la imagen (usa WebP si existe, sino original)
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
        // Si comienza con /storage/, asegurarse que es accesible
        if (str_starts_with($ruta, '/storage/')) {
            return $ruta;
        }        // Si comienza con storage/ (sin /), agregar /
        if (str_starts_with($ruta, 'storage/')) {
            return '/' . $ruta;
        }        // Si es una ruta relativa, agregar /storage/
        return '/storage/' . ltrim($ruta, '/');
    }
}
