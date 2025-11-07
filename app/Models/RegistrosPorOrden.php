<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Festivo;
use App\Traits\Auditable;

class RegistrosPorOrden extends Model
{
    use Auditable;
    protected $table = 'registros_por_orden';

    protected $guarded = [];

    public $timestamps = false;  // Desactivar timestamps para evitar error por columnas ausentes

    protected $festivos = [];

    public function setFestivos(array $festivos)
    {
        $this->festivos = $festivos;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->total_de_dias_ = $model->getTotalDeDiasAttribute();
        });
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
        
        if ($inicio->isWeekend() || in_array($inicio->toDateString(), $festivos)) $businessDays--;
        if ($fin->isWeekend() || in_array($fin->toDateString(), $festivos)) $businessDays--;
        
        return max(0, $businessDays);
    }

    private function countWeekends(Carbon $start, Carbon $end): int
    {
        $totalDays = $start->diffInDays($end) + 1;
        $startDay = $start->dayOfWeek;
        $endDay = $end->dayOfWeek;
        
        $fullWeeks = floor($totalDays / 7);
        $extraDays = $totalDays % 7;
        
        $weekends = $fullWeeks * 2;
        
        for ($i = 0; $i < $extraDays; $i++) {
            $day = ($startDay + $i) % 7;
            if ($day === 0 || $day === 6) $weekends++;
        }
        
        return $weekends;
    }
}
