<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroPisoCorte extends Model
{
    use HasFactory;

    protected $table = 'registro_piso_corte';

    protected $fillable = [
        'fecha',
        'modulo',
        'orden_produccion',
        'hora',
        'tiempo_ciclo',
        'porcion_tiempo',
        'cantidad',
        'producida',
        'paradas_programadas',
        'paradas_no_programadas',
        'tiempo_parada_no_programada',
        'numero_operarios',
        'tiempo_para_programada',
        'tiempo_disponible',
        'meta',
        'eficiencia',
    ];

    protected $casts = [
        'fecha' => 'date',
        'tiempo_ciclo' => 'decimal:2',
        'porcion_tiempo' => 'decimal:2',
        'cantidad' => 'integer',
        'producida' => 'integer',
        'tiempo_parada_no_programada' => 'decimal:2',
        'numero_operarios' => 'integer',
        'tiempo_para_programada' => 'decimal:2',
        'tiempo_disponible' => 'decimal:2',
        'meta' => 'decimal:2',
        'eficiencia' => 'decimal:2',
    ];
}
