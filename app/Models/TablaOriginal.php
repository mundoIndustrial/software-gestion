<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Festivo;
use App\Traits\Auditable;

class TablaOriginal extends Model
{
    use Auditable;
    // Nombre exacto de la tabla en tu BD
    protected $table = 'tabla_original';

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
}
