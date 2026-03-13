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
        'numero_pedido',
        'numero_recibo',
        'prenda_id',
        'fecha_orden',
        'fecha_pago',
        'fecha_despacho',
        'observaciones',
        'dias_demora',
        'marcado',
    ];

    protected $casts = [
        'fecha_pedido' => 'datetime',
        'fecha_llegada' => 'datetime',
        'fecha_orden' => 'datetime',
        'fecha_pago' => 'datetime',
        'fecha_despacho' => 'datetime',
        'recibido' => 'boolean',
        'marcado' => 'boolean',
    ];

    protected $appends = ['dias_demora'];

    /**
     * Relación con el pedido de producción
     */
    public function pedido()
    {
        return $this->belongsTo(PedidoProduccion::class, 'numero_pedido', 'numero_pedido');
    }

    /**
     * Relación con la prenda del pedido
     */
    public function prenda()
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_id');
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
     * Obtener festivos de Colombia desde API externa (con caching y fallback)
     * Intenta: Nager.Date API → Cache Laravel → Festivos estáticos
     */
    private function obtenerFestivosAPI($year)
    {
        // 1. Intentar obtener del cache de Laravel
        $cacheKey = "festivos_colombia_{$year}";
        $cached = \Cache::get($cacheKey);
        
        if ($cached) {
            \Log::info("Festivos obtenidos del cache para {$year}");
            return $cached;
        }

        try {
            // 2. Intentar API externa: Nager.Date (más confiable que nominatina.com)
            // Sin API key requerida, soporta múltiples países
            $url = "https://date.nager.at/api/v3/publicholidays/{$year}/CO";
            
            \Log::info("Intentando obtener festivos desde API: {$url}");
            
            $response = \Http::timeout(5)->get($url);
            
            if ($response->successful()) {
                $festivos = [];
                $data = $response->json();
                
                // Extraer fechas de festivos (formato: YYYY-MM-DD)
                foreach ($data as $festivo) {
                    if (isset($festivo['date'])) {
                        $festivos[] = $festivo['date'];
                    }
                }
                
                // Cachear por 30 días
                \Cache::put($cacheKey, $festivos, now()->addDays(30));
                
                \Log::info("✅ Festivos obtenidos de API para {$year}: " . count($festivos) . " festivos");
                return $festivos;
            } else {
                \Log::warning("API respondió con status {$response->status()} para {$year}");
            }
        } catch (\Exception $e) {
            \Log::warning("Error al obtener festivos de API Nager.Date ({$year}): " . $e->getMessage());
        }
        
        // 3. Fallback: festivos estáticos si la API falla
        \Log::info("Usando festivos estáticos como fallback para {$year}");
        $festivos = $this->obtenerFestivosEstaticos($year);
        
        // Cachear también el fallback
        \Cache::put($cacheKey, $festivos, now()->addDays(30));
        
        return $festivos;
    }
    
    /**
     * Festivos estáticos como fallback
     * Incluye 7 festivos fijos + festivos movibles calculados para años comunes
     * 
     * Nota: Para años específicos, es mejor usar la API
     */
    private function obtenerFestivosEstaticos($year)
    {
        // 7 Festivos fijos
        $festivos = [
            "{$year}-01-01", // Año Nuevo
            "{$year}-05-01", // Día del Trabajo
            "{$year}-07-01", // Día de la Independencia
            "{$year}-07-20", // Grito de Independencia
            "{$year}-08-07", // Batalla de Boyacá
            "{$year}-12-08", // Inmaculada Concepción
            "{$year}-12-25", // Navidad
        ];
        
        // Festivos movibles (aproximaciones para la mayoría de años)
        // En años específicos, la API debería proporcionar los exactos
        $movibles = [
            // Reyes Magos (trasladable)
            "{$year}-01-08",
            // Viernes Santo (aproximado - varía cada año)
            $this->calcularViernesSanto($year),
            // Ascensión (39 días después de Pascua - aproximado)
            $this->calcularAscension($year),
            // Corpus Christi (60 días después de Pascua - aproximado)
            $this->calcularCorpusChristi($year),
            // Sagrado Corazón (68 días después de Pascua - aproximado)
            $this->calcularSagradoCorazon($year),
            // San Pedro y San Pablo (trasladable)
            "{$year}-07-01",
            // Asunción de María
            "{$year}-08-15",
            // Todos los Santos
            "{$year}-11-01",
            // Independencia de Cartagena (trasladable)
            "{$year}-11-11",
        ];
        
        return array_unique(array_merge($festivos, array_filter($movibles)));
    }
    
    /**
     * Calcular Viernes Santo para un año determinado
     * Aproximación usando algoritmo de Computus
     */
    private function calcularViernesSanto($year)
    {
        // Algoritmo de Meeus para Pascua
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($h + $l - 7 * intdiv($h + 11, 22) + 114, 31);
        $day = ($h + $l - 7 * intdiv($h + 11, 22) + 114) % 31 + 1;
        
        // Viernes Santo es 2 días antes de Pascua
        $pascua = Carbon::createFromDate($year, $m, $day);
        return $pascua->subDays(2)->format('Y-m-d');
    }
    
    /**
     * Calcular Ascensión (39 días después de Pascua)
     */
    private function calcularAscension($year)
    {
        $viernesSanto = $this->calcularViernesSanto($year);
        $fecha = Carbon::createFromFormat('Y-m-d', $viernesSanto)->addDays(41);
        return $fecha->format('Y-m-d');
    }
    
    /**
     * Calcular Corpus Christi (60 días después de Pascua)
     */
    private function calcularCorpusChristi($year)
    {
        $viernesSanto = $this->calcularViernesSanto($year);
        $fecha = Carbon::createFromFormat('Y-m-d', $viernesSanto)->addDays(62);
        return $fecha->format('Y-m-d');
    }
    
    /**
     * Calcular Sagrado Corazón (68 días después de Pascua)
     */
    private function calcularSagradoCorazon($year)
    {
        $viernesSanto = $this->calcularViernesSanto($year);
        $fecha = Carbon::createFromFormat('Y-m-d', $viernesSanto)->addDays(70);
        return $fecha->format('Y-m-d');
    }
}
