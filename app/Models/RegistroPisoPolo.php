<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroPisoPolo extends Model
{
    use HasFactory;

    protected $table = 'registro_piso_polo';

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
        'meta',
        'eficiencia',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora' => 'datetime:H:i',
        'tiempo_ciclo' => 'decimal:2',
        'porcion_tiempo' => 'decimal:2',
        'tiempo_parada_no_programada' => 'decimal:2',
        'tiempo_para_programada' => 'decimal:2',
        'meta' => 'decimal:2',
        'eficiencia' => 'decimal:2',
    ];

    public function getTiempoDisponibleAttribute()
    {
        return (3600 * $this->porcion_tiempo * $this->numero_operarios) -
               ($this->tiempo_parada_no_programada ?? 0) -
               ($this->tiempo_para_programada ?? 0);
    }
}
