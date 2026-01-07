<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogoCotizacionTecnicaPrendaFoto extends Model
{
    protected $table = 'logo_cotizacion_tecnica_prendas_fotos';

    protected $fillable = [
        'logo_cotizacion_tecnica_prenda_id',
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
    ];

    /**
     * Relación: Pertenece a una LogoCotizacionTecnicaPrenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(LogoCotizacionTecnicaPrenda::class, 'logo_cotizacion_tecnica_prenda_id');
    }
}
