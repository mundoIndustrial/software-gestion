<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcesoPrenda extends Model
{
    use SoftDeletes;

    protected $table = 'procesos_prenda';

    protected $fillable = [
        'prenda_pedido_id',
        'proceso',
        'fecha_inicio',
        'fecha_fin',
        'dias_duracion',
        'encargado',
        'estado_proceso',
        'observaciones',
        'codigo_referencia',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Relación: Un proceso pertenece a una prenda
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }
}
