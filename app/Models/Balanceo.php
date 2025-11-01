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
        // Calcular SAM total
        $this->sam_total = $this->operaciones()->sum('sam');

        // Calcular tiempo disponible
        $this->tiempo_disponible_horas = $this->total_operarios * $this->turnos * $this->horas_por_turno;
        $this->tiempo_disponible_segundos = $this->tiempo_disponible_horas * 3600;

        // Calcular meta teórica
        if ($this->sam_total > 0) {
            $this->meta_teorica = floor($this->tiempo_disponible_segundos / $this->sam_total);
        }

        // Encontrar cuello de botella (operación con mayor SAM)
        $cuelloBotella = $this->operaciones()->orderBy('sam', 'desc')->first();
        if ($cuelloBotella) {
            $this->operario_cuello_botella = $cuelloBotella->operario;
            $this->tiempo_cuello_botella = $cuelloBotella->sam;
            $this->sam_real = $cuelloBotella->sam;
            
            // Calcular meta real basada en el cuello de botella
            $this->meta_real = floor($this->tiempo_disponible_segundos / $this->sam_real);
            
            // Meta sugerida al 85%
            $this->meta_sugerida_85 = floor($this->meta_real * 0.85);
        }

        $this->save();
    }
}
