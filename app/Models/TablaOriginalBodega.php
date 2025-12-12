<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Festivo;

class TablaOriginalBodega extends Model
{
    // Nombre exacto de la tabla en tu BD
    protected $table = 'tabla_original_bodega';

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
                return $dias > 0 ? $dias : 0;
            }
            return 0;
        }

        $dias = $this->calcularDiasHabiles($fechaCreacion, Carbon::now(), $festivos);
        return $dias > 0 ? $dias : 0;
    }

    private function calcularDiasHabiles(Carbon $inicio, Carbon $fin, array $festivos): int
    {
        $diasCalculados = 0;
        $actual = $inicio->copy();
        
        while ($actual <= $fin) {
            // Verificar si no es sábado (6) ni domingo (0)
            if ($actual->dayOfWeek !== 0 && $actual->dayOfWeek !== 6) {
                // Verificar si no es festivo
                if (!in_array($actual->toDateString(), $festivos)) {
                    $diasCalculados++;
                }
            }
            $actual->addDay();
        }
        
        // Restar 1 porque no se cuenta el día de inicio
        return max(0, $diasCalculados - 1);
    }
}
