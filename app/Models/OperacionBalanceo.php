<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperacionBalanceo extends Model
{
    use HasFactory;

    protected $table = 'operaciones_balanceo';

    protected $fillable = [
        'balanceo_id',
        'letra',
        'operacion',
        'precedencia',
        'maquina',
        'sam',
        'operario',
        'op',
        'seccion',
        'operario_a',
        'orden',
    ];

    protected $casts = [
        'sam' => 'double',
        'orden' => 'integer',
    ];

    /**
     * Valores por defecto para campos opcionales
     */
    protected $attributes = [
        'letra' => '',
        'operacion' => '',
        'precedencia' => null,
        'maquina' => null,
        'sam' => 0,
        'operario' => null,
        'op' => null,
        'seccion' => 'DEL',
        'operario_a' => null,
        'orden' => 0,
    ];

    /**
     * Relación con balanceo
     */
    public function balanceo()
    {
        return $this->belongsTo(Balanceo::class);
    }
}
