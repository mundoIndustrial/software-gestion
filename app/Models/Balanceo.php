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
        'estado_completo',
    ];

    protected $casts = [
        'total_operarios' => 'integer',
        'turnos' => 'integer',
        'horas_por_turno' => 'double',
        'tiempo_disponible_horas' => 'double',
        'tiempo_disponible_segundos' => 'double',
        'sam_total' => 'double',
        'meta_teorica' => 'integer',
        'meta_real' => 'double',
        'tiempo_cuello_botella' => 'double',
        'sam_real' => 'double',
        'meta_sugerida_85' => 'integer',
        'activo' => 'boolean',
        'estado_completo' => 'boolean',
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
        // Redondear a 1 decimal para evitar errores de precisión de punto flotante
        $this->sam_total = round($this->operaciones()->sum('sam'), 1);

        // Calcular tiempo disponible en horas: Horas/turno * Turnos * Total operarios
        $this->tiempo_disponible_horas = $this->horas_por_turno * $this->turnos * $this->total_operarios;
        
        // Calcular tiempo disponible en segundos: T. Disponible Horas * 3600
        $this->tiempo_disponible_segundos = $this->tiempo_disponible_horas * 3600;

        // Calcular meta teórica: T. Disponible Segundos / SAM
        if ($this->sam_total > 0) {
            $this->meta_teorica = round($this->tiempo_disponible_segundos / $this->sam_total);
            
            // Meta real al 90% de la meta teórica (SIN floor para mantener decimales)
            $this->meta_real = $this->meta_teorica * 0.90;
        }

        // Encontrar cuello de botella (operación con mayor SAM)
        $cuelloBotella = $this->operaciones()->orderBy('sam', 'desc')->first();
        if ($cuelloBotella) {
            $this->operario_cuello_botella = $cuelloBotella->operario;
            $this->tiempo_cuello_botella = $cuelloBotella->sam;
            
            // SAM Real = Tiempo cuello de botella * Total operarios
            $this->sam_real = $cuelloBotella->sam * $this->total_operarios;
            
            // Meta Real (cuello de botella) = T. Disponible Segundos / SAM Real
            // Evitar división por cero
            if ($this->sam_real > 0) {
                $metaRealCuelloBotella = floor($this->tiempo_disponible_segundos / $this->sam_real);
                
                // Meta sugerida al 85% del cuello de botella
                $this->meta_sugerida_85 = floor($metaRealCuelloBotella * 0.85);
            } else {
                $this->meta_sugerida_85 = null;
            }
        }

        $this->save();
    }
}
