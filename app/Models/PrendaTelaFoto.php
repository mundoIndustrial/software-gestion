<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PrendaTelaFoto - Modelo para fotos de telas de prendas
 *
 * Representa una foto de tela asociada a una prenda en una cotización
 */
class PrendaTelaFoto extends Model
{
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
        'orden' => 'integer',
        'ancho' => 'integer',
        'alto' => 'integer',
        'tamaño' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Una foto de tela pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaCot::class, 'prenda_cot_id');
    }

    /**
     * Scope: Ordenar por orden
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden', 'asc');
    }

    /**
     * Obtener ruta de acceso web
     */
    public function getRutaWebAttribute(): string
    {
        return $this->ruta_webp ?? $this->ruta_original ?? '';
    }

    /**
     * Obtener dimensiones
     */
    public function getDimensionesAttribute(): array
    {
        return [
            'ancho' => $this->ancho,
            'alto' => $this->alto,
            'tamaño' => $this->tamaño,
        ];
    }
}
