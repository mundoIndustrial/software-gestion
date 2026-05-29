<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LavanderiaPrendaAgregada extends Model
{
    protected $table = 'lavanderia_prenda_agregada';

    protected $fillable = [
        'lavanderia_movimiento_id',
        'descripcion',
    ];

    /**
     * Relación: Una prenda agregada pertenece a un movimiento
     */
    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(LavanderiaMovimiento::class, 'lavanderia_movimiento_id');
    }

    /**
     * Relación: Una prenda agregada tiene muchas tallas
     */
    public function tallas(): HasMany
    {
        return $this->hasMany(LavanderiaMovimientoTalla::class, 'prenda_agregada_id');
    }
}
