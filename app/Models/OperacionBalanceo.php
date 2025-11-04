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
     * Relación con balanceo
     */
    public function balanceo()
    {
        return $this->belongsTo(Balanceo::class);
    }
}
