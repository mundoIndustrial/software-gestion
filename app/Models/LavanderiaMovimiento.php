<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LavanderiaMovimiento extends Model
{
    protected $table = 'lavanderia_movimientos';

    protected $fillable = [
        'tipo_movimiento',
        'fecha_movimiento',
        'firma_movimiento',
        'fecha_firma',
        'novedad',
        'estado',
    ];

    protected $casts = [
        'fecha_movimiento' => 'datetime',
        'fecha_firma' => 'datetime',
    ];

    /**
     * Relación: Un movimiento tiene muchos recibos
     */
    public function recibos(): HasMany
    {
        return $this->hasMany(LavanderiaMovimientoRecibo::class, 'lavanderia_movimiento_id');
    }

    /**
     * Relación: Un movimiento tiene muchas tallas
     */
    public function tallas(): HasMany
    {
        return $this->hasMany(LavanderiaMovimientoTalla::class, 'lavanderia_movimiento_id');
    }
}

