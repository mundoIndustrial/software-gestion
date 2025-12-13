<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogoFotoCot extends Model
{
    use HasFactory;

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
        'logo_cotizacion_id' => 'integer',
        'orden' => 'integer',
        'ancho' => 'integer',
        'alto' => 'integer',
        'tamaño' => 'integer',
    ];

    /**
     * Relación: Una foto de logo pertenece a una cotización de logo
     */
    public function logoCotizacion()
    {
        return $this->belongsTo(LogoCotizacion::class, 'logo_cotizacion_id');
    }
}
