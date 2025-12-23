<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrendaTelaFotoCot extends Model
{
    use HasFactory;

    protected $table = 'prenda_tela_fotos_cot';

    protected $fillable = [
        'prenda_cot_id',
        'prenda_tela_cot_id',
        'tela_index',
        'ruta_original',
        'ruta_webp',
        'ruta_miniatura',
        'orden',
        'ancho',
        'alto',
        'tamaño',
    ];

    protected $casts = [
        'prenda_cot_id' => 'integer',
        'prenda_tela_cot_id' => 'integer',
        'tela_index' => 'integer',
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
     * Relación: Una foto de tela pertenece a una prenda de cotización
     */
    public function prendaCot()
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }

    /**
     * Relación: Una foto de tela pertenece a una prenda_tela_cot (para acceder a tela y color)
     */
    public function prendaTelaCot()
    {
        return $this->belongsTo(PrendaTelaCot::class, 'prenda_tela_cot_id');
    }

    /**
     * Relación: Acceder directamente a TelaPrenda a través de prenda_tela_cot
     */
    public function telaPrenda()
    {
        return $this->hasOneThrough(
            TelaPrenda::class,
            PrendaTelaCot::class,
            'id',
            'id',
            'prenda_tela_cot_id',
            'tela_id'
        );
    }

    /**
     * Relación: Acceder directamente a ColorPrenda a través de prenda_tela_cot
     */
    public function colorPrenda()
    {
        return $this->hasOneThrough(
            ColorPrenda::class,
            PrendaTelaCot::class,
            'id',
            'id',
            'prenda_tela_cot_id',
            'color_id'
        );
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
