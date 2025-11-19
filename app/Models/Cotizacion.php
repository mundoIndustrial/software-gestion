<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'numero_cotizacion',
        'fecha',
        'cliente',
        'asesora',
        'cotizar_segun_indicaciones'
    ];

    protected $casts = [
        'fecha' => 'date'
    ];

    /**
     * Relación con prendas de la cotización
     */
    public function prendas(): HasMany
    {
        return $this->hasMany(PrendaCotizacion::class);
    }

    /**
     * Relación con formatos de cotización
     */
    public function formatos(): HasMany
    {
        return $this->hasMany(FormatoCotizacion::class);
    }
}
