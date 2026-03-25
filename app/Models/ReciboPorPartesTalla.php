<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReciboPorPartesTalla extends Model
{
    protected $table = 'recibos_por_partes_tallas';

    protected $fillable = [
        'recibo_por_partes_id',
        'talla',
        'cantidad',
        'color_nombre',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    /**
     * Relación: Pertenece a un ReciboPorPartes
     */
    public function reciboPorPartes(): BelongsTo
    {
        return $this->belongsTo(ReciboPorPartes::class, 'recibo_por_partes_id');
    }
}
