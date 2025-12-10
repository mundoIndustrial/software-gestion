<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LogoFoto - Modelo para fotos de logos
 *
 * Representa una foto de logo asociada a una cotización
 * Máximo 5 fotos por logo
 */
class LogoFoto extends Model
{
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
        'orden' => 'integer',
        'ancho' => 'integer',
        'alto' => 'integer',
        'tamaño' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Una foto de logo pertenece a un logo
     */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(LogoCotizacion::class, 'logo_cotizacion_id');
    }

    /**
     * Scope: Ordenar por orden
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden', 'asc');
    }

    /**
     * Scope: Máximo 5 fotos
     */
    public function scopeLimitado($query)
    {
        return $query->limit(5);
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

    /**
     * Validar que no exceda máximo de 5 fotos
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $cantidad = LogoFoto::where('logo_cotizacion_id', $model->logo_cotizacion_id)->count();

            if ($cantidad >= 5) {
                throw new \DomainException('El logo no puede tener más de 5 fotos');
            }
        });
    }
}
