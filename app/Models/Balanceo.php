<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Balanceo extends Model
{
    use HasFactory;

    protected $fillable = [
        'prenda_id',
        'version',
        'total_operarios',
        'turnos',
        'horas_por_turno',
        'tiempo_disponible_horas',
        'tiempo_disponible_segundos',
        'sam_total',
        'meta_teorica',
        'meta_real',
        'operario_cuello_botella',
        'tiempo_cuello_botella',
        'sam_real',
        'meta_sugerida_85',
        'activo',
    ];

    protected $casts = [
        'total_operarios' => 'integer',
        'turnos' => 'integer',
        'horas_por_turno' => 'decimal:2',
        'tiempo_disponible_horas' => 'decimal:2',
        'tiempo_disponible_segundos' => 'decimal:2',
        'sam_total' => 'decimal:2',
        'meta_teorica' => 'integer',
        'meta_real' => 'integer',
        'tiempo_cuello_botella' => 'decimal:2',
        'sam_real' => 'decimal:2',
        'meta_sugerida_85' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Relación con prenda
     */
    public function prenda()
    {
        return $this->belongsTo(Prenda::class);
    }

    /**
     * Relación con operaciones
     */
    public function operaciones()
    {
        return $this->hasMany(OperacionBalanceo::class)->orderBy('orden');
    }

    /**
     * Calcular automáticamente los valores del balanceo
     */
    public function calcularMetricas()
    {
        // Calcular SAM total (suma de todos los SAM de las operaciones)
        $this->sam_total = $this->operaciones()->sum('sam');

        // Calcular tiempo disponible en horas: Horas/turno * Turnos * Total operarios
        $this->tiempo_disponible_horas = $this->horas_por_turno * $this->turnos * $this->total_operarios;
        
        // Calcular tiempo disponible en segundos: T. Disponible Horas * 3600
        $this->tiempo_disponible_segundos = $this->tiempo_disponible_horas * 3600;

        // Calcular meta teórica: T. Disponible Segundos / SAM
        if ($this->sam_total > 0) {
            $this->meta_teorica = floor($this->tiempo_disponible_segundos / $this->sam_total);
            
            // Meta real al 90% de la meta teórica
            $this->meta_real = floor($this->meta_teorica * 0.90);
        }

        // Encontrar cuello de botella (operación con mayor SAM)
        $cuelloBotella = $this->operaciones()->orderBy('sam', 'desc')->first();
        if ($cuelloBotella) {
            $this->operario_cuello_botella = $cuelloBotella->operario;
            $this->tiempo_cuello_botella = $cuelloBotella->sam;
            
            // SAM Real = Tiempo cuello de botella * Total operarios
            $this->sam_real = $cuelloBotella->sam * $this->total_operarios;
            
            // Meta Real (cuello de botella) = T. Disponible Segundos / SAM Real
            $metaRealCuelloBotella = floor($this->tiempo_disponible_segundos / $this->sam_real);
            
            // Meta sugerida al 85% del cuello de botella
            $this->meta_sugerida_85 = floor($metaRealCuelloBotella * 0.85);
        }

        $this->save();
    }
}
