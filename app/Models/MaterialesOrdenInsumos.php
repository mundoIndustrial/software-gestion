<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MaterialesOrdenInsumos extends Model
{
    protected $table = 'materiales_orden_insumos';

    protected $fillable = [
        'nombre_material',
        'fecha_pedido',
        'fecha_llegada',
        'recibido',
        'tabla_original_pedido',
    ];

    protected $casts = [
        'fecha_pedido' => 'date',
        'fecha_llegada' => 'date',
        'recibido' => 'boolean',
    ];

    protected $appends = ['dias_demora'];

    /**
     * Relación con la orden original
     */
    public function orden()
    {
        return $this->belongsTo(TablaOriginal::class, 'tabla_original_pedido', 'pedido');
    }

    /**
     * Calcular días de demora automáticamente
     * Diferencia entre fecha_llegada y fecha_pedido (excluyendo sábados, domingos y festivos)
     */
    public function getDiasDemoraAttribute()
    {
        if ($this->fecha_pedido && $this->fecha_llegada) {
            $fechaPedido = $this->fecha_pedido;
            $fechaLlegada = $this->fecha_llegada;
            
            // Obtener festivos de Colombia desde API
            $festivos = $this->obtenerFestivosAPI($fechaPedido->year);
            
            $diasLaborales = 0;
            $fecha = $fechaPedido->copy();
            
            while ($fecha <= $fechaLlegada) {
                // Verificar si no es sábado (6) ni domingo (0)
                if ($fecha->dayOfWeek !== 0 && $fecha->dayOfWeek !== 6) {
                    // Verificar si no es festivo
                    $fechaFormato = $fecha->format('Y-m-d');
                    if (!in_array($fechaFormato, $festivos)) {
                        $diasLaborales++;
                    }
                }
                $fecha->addDay();
            }
            
            // Restar 1 porque no contamos el día de inicio
            return max(0, $diasLaborales - 1);
        }
        return null;
    }
    
    /**
     * Obtener festivos de Colombia desde API
     */
    private function obtenerFestivosAPI($year)
    {
        try {
            // API de festivos colombianos
            $url = "https://www.nominatina.com/api/v1/holidays/CO/{$year}";
            
            $response = \Http::timeout(5)->get($url);
            
            if ($response->successful()) {
                $festivos = [];
                $data = $response->json();
                
                // Extraer fechas de festivos
                foreach ($data as $festivo) {
                    if (isset($festivo['date'])) {
                        $festivos[] = $festivo['date'];
                    }
                }
                
                return $festivos;
            }
        } catch (\Exception $e) {
            \Log::warning('Error al obtener festivos de API: ' . $e->getMessage());
        }
        
        // Fallback: festivos estáticos si la API falla
        return $this->obtenerFestivosEstaticos($year);
    }
    
    /**
     * Festivos estáticos como fallback
     */
    private function obtenerFestivosEstaticos($year)
    {
        return [
            "{$year}-01-01", // Año Nuevo
            "{$year}-01-08", // Reyes Magos
            "{$year}-03-29", // Viernes Santo (aproximado)
            "{$year}-05-01", // Día del Trabajo
            "{$year}-06-03", // Corpus Christi (aproximado)
            "{$year}-06-10", // Sagrado Corazón (aproximado)
            "{$year}-07-01", // San Pedro y San Pablo
            "{$year}-07-20", // Grito de Independencia
            "{$year}-08-07", // Batalla de Boyacá
            "{$year}-08-15", // Asunción de María
            "{$year}-11-01", // Todos los Santos
            "{$year}-11-11", // Independencia de Cartagena
            "{$year}-12-08", // Inmaculada Concepción
            "{$year}-12-25", // Navidad
        ];
    }
}
