<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent: EppImagen
 * 
 * Capa de persistencia - Encapsula acceso a tabla "epp_imagenes"
 */
class EppImagen extends Model
{
    protected $table = 'epp_imagenes';

    protected $fillable = [
        'epp_id',
        'archivo',
        'principal',
        'orden',
    ];

    protected $casts = [
        'principal' => 'boolean',
        'orden' => 'integer',
        'created_at' => 'datetime',
    ];

    const UPDATED_AT = null; // Las imágenes no se actualizan, solo se crean y eliminan

    /**
     * Relación: Una imagen pertenece a un EPP
     */
    public function epp(): BelongsTo
    {
        return $this->belongsTo(Epp::class, 'epp_id');
    }

    /**
     * Obtener URL construida de la imagen
     */
    public function getUrlAttribute(): string
    {
        if (!$this->relationLoaded('epp')) {
            $this->loadMissing('epp');
        }

        return '/storage/epp/' . $this->epp->codigo . '/' . $this->archivo;
    }
}
