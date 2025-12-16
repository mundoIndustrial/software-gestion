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
        }
        // Si es una ruta relativa, agregar /storage/
        return '/storage/' . ltrim($ruta, '/');
    }
}
