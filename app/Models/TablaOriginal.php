<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Festivo;
use App\Traits\Auditable;
use App\Observers\TablaOriginalObserver;
use App\Services\FestivosColombiaService;

class TablaOriginal extends Model
{
    use Auditable;
    // Nombre exacto de la tabla en tu BD
    protected $table = 'tabla_original';

    /**
     * Registrar observers del modelo
     */
    protected static function boot()
    {
        parent::boot();
        static::observe(TablaOriginalObserver::class);
    }

    // Definir la clave primaria como 'pedido'
    protected $primaryKey = 'pedido';

    // Indicar que la clave primaria no es autoincremental
    public $incrementing = false;

    // Tipo de clave primaria (string o int)
    protected $keyType = 'int';

    // Permite asignación masiva de todos los campos
    protected $guarded = [];

    // Tu tabla no tiene columnas created_at / updated_at
    public $timestamps = false;

    protected $festivos = [];

    public function setFestivos(array $festivos)
    {
        $this->festivos = $festivos;
    }

    public function getTotalDeDiasAttribute(): int
    {
        $fechaCreacion = Carbon::parse($this->fecha_de_creacion_de_orden);
        $festivos = $this->festivos ?: Festivo::pluck('fecha')->toArray();

        if ($this->estado === 'Entregado') {
            $fechaDespacho = $this->despacho ? Carbon::parse($this->despacho) : null;
            if ($fechaDespacho) {
                $dias = $this->calcularDiasHabiles($fechaCreacion, $fechaDespacho, $festivos);
                return $dias > 0 ? $dias - 1 : 0;
            }
            return 0; // Si no hay fecha de despacho, no se cuentan días
        }

        $dias = $this->calcularDiasHabiles($fechaCreacion, Carbon::now(), $festivos);
        return $dias > 0 ? $dias - 1 : 0;
    }

    private function calcularDiasHabiles(Carbon $inicio, Carbon $fin, array $festivos): int
    {
        $totalDays = $inicio->diffInDays($fin) + 1;
        $weekends = $this->countWeekends($inicio, $fin);
        $holidaysInRange = count(array_filter($festivos, function ($festivo) use ($inicio, $fin) {
            return Carbon::parse($festivo)->between($inicio, $fin);
        }));
        
        $businessDays = $totalDays - $weekends - $holidaysInRange;
        
        // Adjust if start or end is weekend/holiday (but since we count inclusive, fine-tune)
        if ($inicio->isWeekend() || in_array($inicio->toDateString(), $festivos)) $businessDays--;
        if ($fin->isWeekend() || in_array($fin->toDateString(), $festivos)) $businessDays--;
        
        return max(0, $businessDays);
    }

    private function countWeekends(Carbon $start, Carbon $end): int
    {
        $totalDays = $start->diffInDays($end) + 1;
        $startDay = $start->dayOfWeek; // 0=Sun, 6=Sat
        $endDay = $end->dayOfWeek;
        
        $fullWeeks = floor($totalDays / 7);
        $extraDays = $totalDays % 7;
        
        $weekends = $fullWeeks * 2; // 2 weekends per week
        
        // Count weekends in extra days
        for ($i = 0; $i < $extraDays; $i++) {
            $day = ($startDay + $i) % 7;
            if ($day === 0 || $day === 6) $weekends++;
        }
        
        return $weekends;
    }

    /**
     * Calcula la fecha estimada de entrega basada en:
     * - Fecha de creación de orden
     * - Días de entrega especificados
     * - Excluyendo sábados, domingos y festivos
     */
    public function calcularFechaEstimadaEntrega(): ?Carbon
    {
        if (!$this->fecha_de_creacion_de_orden || !$this->dia_de_entrega) {
            return null;
        }

        $fechaInicio = Carbon::parse($this->fecha_de_creacion_de_orden);
        $diasRequeridos = intval($this->dia_de_entrega);
        
        // Usar FestivosColombiaService en lugar de tabla BD para consistencia
        $fechaFin = $fechaInicio->copy()->addDays($diasRequeridos * 3);
        $festivos = FestivosColombiaService::festivosEnRango($fechaInicio, $fechaFin);
        
        \Log::info("[MODELO] calcularFechaEstimadaEntrega - Iniciando cálculo", [
            'pedido' => $this->pedido,
            'fecha_creacion' => $this->fecha_de_creacion_de_orden,
            'dias_requeridos' => $diasRequeridos,
            'cantidad_festivos_api' => count($festivos),
            'festivos_api' => $festivos
        ]);

        // Comenzar desde el día siguiente
        $fechaActual = $fechaInicio->copy()->addDay();
        $diasContados = 0;

        // Contar días hábiles hasta alcanzar los días requeridos
        while ($diasContados < $diasRequeridos) {
            // Verificar si es fin de semana o festivo
            if (!$fechaActual->isWeekend() && !in_array($fechaActual->toDateString(), $festivos)) {
                $diasContados++;
            }

            // Si aún no hemos contado todos los días, avanzar al siguiente
            if ($diasContados < $diasRequeridos) {
                $fechaActual->addDay();
            }
        }

        \Log::info("[MODELO] calcularFechaEstimadaEntrega - Cálculo completado", [
            'pedido' => $this->pedido,
            'fecha_estimada' => $fechaActual->format('Y-m-d'),
            'fecha_estimada_formateada' => $fechaActual->format('d/m/Y'),
            'dias_contados' => $diasContados
        ]);

        return $fechaActual;
    }

    /**
     * Accessor para obtener la fecha estimada de entrega formateada
     */
    public function getFechaEstimadaEntregaFormattedAttribute(): ?string
    {
        $fecha = $this->calcularFechaEstimadaEntrega();
        return $fecha ? $fecha->format('d/m/Y') : null;
    }

}
