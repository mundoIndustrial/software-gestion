<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class RegistroPisoCorte extends Model
{
    use HasFactory, Auditable;

    protected $table = 'registro_piso_corte';

    protected $fillable = [
        'fecha',
        'modulo',
        'orden_produccion',
        'hora_id',
        'operario_id',
        'actividad',
        'maquina_id',
        'tela_id',
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
        'tipo_extendido',
        'numero_capas',
        'tiempo_extendido',
        'trazado',
        'tiempo_trazado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
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

    // Relationships
    public function hora()
    {
        return $this->belongsTo(Hora::class);
    }

    public function operario()
    {
        return $this->belongsTo(User::class, 'operario_id');
    }

    public function maquina()
    {
        return $this->belongsTo(Maquina::class);
    }

    public function tela()
    {
        return $this->belongsTo(Tela::class);
    }

    // REMOVED: This accessor was interfering with the tiempo_ciclo field
    // The tiempo_ciclo is now stored directly in the registro_piso_corte table
    // and should not be overridden by the pivot table value
}
