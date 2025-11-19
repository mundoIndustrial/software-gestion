<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostoPrenda extends Model
{
    protected $table = 'costos_prenda';

    protected $fillable = [
        'prenda_cotizacion_id',
        'componente_prenda_id',
        'costo'
    ];

    protected $casts = [
        'costo' => 'decimal:2'
    ];

    /**
     * Relación con prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaCotizacion::class, 'prenda_cotizacion_id');
    }

    /**
     * Relación con componente
     */
    public function componente(): BelongsTo
    {
        return $this->belongsTo(ComponentePrenda::class, 'componente_prenda_id');
    }

    /**
     * Obtener costo total de la prenda
     */
    public static function getTotalCosto($prendaId)
    {
        return self::where('prenda_cotizacion_id', $prendaId)->sum('costo');
    }
}
