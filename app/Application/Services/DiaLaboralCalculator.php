<?php

namespace App\Application\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DiaLaboralCalculator
 * 
 * Responsabilidad: Calcular días laborales (excluyendo fines de semana y festivos)
 * Obtiene festivos dinámicamente desde la API de Nager.Date
 */
class DiaLaboralCalculator
{
    /**
     * Calcular días laborales desde la creación de la orden hasta ahora
     * Excluye: sábados, domingos y días festivos
     * Solo cuenta: lunes a viernes (días laborales)
     * 
     * Los festivos se obtienen: primero desde API, fallback a cálculo local
     * 
     * @param Carbon|null $fechaInicio
     * @return int
     */
    public function calcular(?Carbon $fechaInicio): int
    {
        try {
            if (!$fechaInicio) {
                return 0;
            }

            $fechaInicio = $fechaInicio->copy();
            $fechaFin = Carbon::now();

            // Validar que la fecha de inicio no sea posterior a hoy
            if ($fechaInicio->isAfter($fechaFin)) {
                return 0;
            }

            // Obtener los festivos del rango de años (API con fallback)
            $festivosSet = $this->obtenerFestivosDelRango($fechaInicio, $fechaFin);

            // Comenzar desde el día siguiente a la fecha de inicio
            $current = $fechaInicio->copy()->addDay();
            $totalDays = 0;

            // Iterar día a día hasta hoy
            while ($current <= $fechaFin) {
                $dateString = $current->format('Y-m-d');
                
                // Verificar que NO sea fin de semana
                $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                
                // Verificar que NO sea festivo
                $isFestivo = isset($festivosSet[$dateString]);

                // Contar solo si es día laboral y no es festivo
                if (!$isWeekend && !$isFestivo) {
                    $totalDays++;
                }

                $current->addDay();
            }

            Log::info("[DiaLaboralCalculator] Cálculo final: {dias} días laborales desde {inicio}", [
                'dias' => $totalDays,
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d'),
                'festivos_encontrados' => count($festivosSet)
            ]);

            return max(0, $totalDays);
        } catch (\Exception $e) {
            Log::error('[DiaLaboralCalculator] Error calculando días laborales: ' . $e->getMessage(), [
                'fecha_inicio' => $fechaInicio,
                'error' => $e->getMessage()
            ]);
            // En caso de error crítico, devolver diferencia básica
            return max(0, $fechaInicio->diffInDays($fechaFin));
        }
    }

    /**
     * Obtener festivos de Colombia desde la API de Nager.Date para el rango de años
     * 
     * @param Carbon $fechaInicio
     * @param Carbon $fechaFin
     * @return array Mapa de festivos (fecha => true)
     */
    private function obtenerFestivosDelRango(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        try {
            $festivos = [];
            $currentYear = $fechaInicio->year;
            $endYear = $fechaFin->year;

            // Obtener festivos para cada año en el rango
            for ($year = $currentYear; $year <= $endYear; $year++) {
                $festivosDelAnio = $this->obtenerFestivosDelAnio($year);
                $festivos = array_merge($festivos, $festivosDelAnio);
            }

            return $festivos;
        } catch (\Exception $e) {
            Log::warning('[DiaLaboralCalculator] Error obteniendo festivos de API: ' . $e->getMessage());
            // Devolver array vacío para continuar el cálculo sin festivos
            return [];
        }
    }

    /**
     * Obtener festivos de un año específico desde la API de Nager.Date
     * Intenta múltiples endpoints hasta obtener éxito
     * 
     * @param int $year Año a consultar
     * @return array Mapa de festivos (fecha => true)
     */
    private function obtenerFestivosDelAnio(int $year): array
    {
        // Intento 1: api.nager.date (original)
        $festivos = $this->intentarNagerDateAPI("https://api.nager.date/v3/PublicHolidays/{$year}/CO", "api.nager.date", $year);
        if (!empty($festivos)) {
            return $festivos;
        }

        // Intento 2: date.nager.at (dominio alternativo - FUNCIONA SIEMPRE)
        $festivos = $this->intentarNagerDateAPI("https://date.nager.at/api/v3/PublicHolidays/{$year}/CO", "date.nager.at", $year);
        if (!empty($festivos)) {
            return $festivos;
        }

        // Fallback: usar cálculo local
        Log::warning("[DiaLaboralCalculator] Ambos endpoints de Nager.Date fallaron, usando cálculo local para {year}", [
            'year' => $year,
            'source' => 'LOCAL_FALLBACK'
        ]);
        return $this->obtenerFestivosLocales($year);
    }

    /**
     * Intentar obtener festivos desde un endpoint específico de Nager.Date
     * 
     * @param string $url URL a intentar
     * @param string $source Identificador de la fuente (para logging)
     * @param int $year Año a consultar
     * @return array Mapa de festivos (fecha => true), vacío si falla
     */
    private function intentarNagerDateAPI(string $url, string $source, int $year): array
    {
        try {
            // Timeout de 5 segundos para no bloquear la aplicación
            // followRedirects() es importante para nager.date/api/v3
            $response = Http::timeout(5)
                ->followRedirects()
                ->withoutVerifying()
                ->withHeaders(['User-Agent' => 'Laravel-App/1.0'])
                ->get($url);

            if (!$response->successful()) {
                Log::debug("[DiaLaboralCalculator] {source} no disponible (status {status}) para año {year}", [
                    'source' => $source,
                    'status' => $response->status(),
                    'year' => $year
                ]);
                return [];
            }

            $festivosData = $response->json();
            $festivos = [];

            if (is_array($festivosData)) {
                foreach ($festivosData as $festivo) {
                    if (isset($festivo['date'])) {
                        $festivos[$festivo['date']] = true;
                    }
                }
            }

            Log::info("[DiaLaboralCalculator] 🌐 {source} devolvió {count} festivos para año {year}", [
                'count' => count($festivos),
                'year' => $year,
                'source' => $source,
                'festivos' => implode(', ', array_keys($festivos))
            ]);

            return $festivos;
        } catch (\Exception $e) {
            Log::debug("[DiaLaboralCalculator] {source} error para {year}: " . $e->getMessage(), [
                'source' => $source,
                'year' => $year,
                'error' => class_basename($e)
            ]);
            return [];
        }
    }

    /**
     * Festivos locales de Colombia como fallback cuando la API no está disponible
     * Lista de festivos colombianos para los años 2020-2035
     * 
     * @param int $year Año requerido
     * @return array Mapa de festivos (fecha => true)
     */
    private function obtenerFestivosLocales(int $year): array
    {
        // Festivos fijos de Colombia
        $festivosLocales = [
            // Año Nuevo
            "{$year}-01-01",
            // Reyes (trasladado)
            "{$year}-01-06",
            // San José (trasladado)
            "{$year}-03-19",
            // Día del Trabajo
            "{$year}-05-01",
            // Corpus Christi (trasladado)
            "{$year}-06-17",
            // Sagrado Corazón (trasladado)
            "{$year}-07-01",
            // Independencia (fijo)
            "{$year}-07-01",
            // Batalla de Boyacá
            "{$year}-08-07",
            // Asunción de María (fijo)
            "{$year}-08-15",
            // Todos los Santos (trasladado)
            "{$year}-11-02",
            // Inmaculada Concepción
            "{$year}-12-08",
            // Navidad
            "{$year}-12-25",
        ];

        // Festivos móviles (aproximados basados en Pascua)
        // Para 2026: Viernes Santo = 3 de abril, Jueves Santo = 2 de abril
        $festivosMoviles = $this->calcularFestivosMoviles($year);

        $festivos = [];
        foreach (array_merge($festivosLocales, $festivosMoviles) as $fecha) {
            $festivos[$fecha] = true;
        }

        Log::debug("[DiaLaboralCalculator] Usando festivos locales para año {$year}", [
            'count' => count($festivos),
            'year' => $year
        ]);

        return $festivos;
    }

    /**
     * Calcular festivos móviles basados en la Pascua (Viernes Santo, Jueves Santo, etc.)
     * Usa algoritmo de Computus
     * 
     * @param int $year Año
     * @return array Fechas de festivos móviles
     */
    private function calcularFestivosMoviles(int $year): array
    {
        // Calcular Pascua (Viernes Santo es 2 días antes)
        $pascua = $this->calcularPascua($year);
        
        if (!$pascua) {
            return [];
        }

        $juevesSanto = $pascua->copy()->subDays(3);
        $viernesSanto = $pascua->copy()->subDays(2);
        $ascension = $pascua->copy()->addDays(39);
        $corpusChristi = $pascua->copy()->addDays(60);

        return [
            $juevesSanto->format('Y-m-d'),
            $viernesSanto->format('Y-m-d'),
            $ascension->format('Y-m-d'),
            $corpusChristi->format('Y-m-d'),
        ];
    }

    /**
     * Algoritmo de Computus para calcular la fecha de Pascua
     * Devuelve el Domingo de Pascua
     * 
     * @param int $year Año
     * @return Carbon fecha del Domingo de Pascua
     */
    private function calcularPascua(int $year): ?Carbon
    {
        try {
            $a = $year % 19;
            $b = (int)($year / 100);
            $c = $year % 100;
            $d = (int)($b / 4);
            $e = $b % 4;
            $f = (int)(($b + 8) / 25);
            $g = (int)(($b - $f + 1) / 3);
            $h = (19 * $a + $b - $d - $g + 15) % 30;
            $i = (int)($c / 4);
            $k = $c % 4;
            $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
            $m = (int)(($h + $l - 7 * (int)(($h + $l) / 11) + 114) / 31);
            $p = ($h + $l - 7 * (int)(($h + $l) / 11) + 114) % 31 + 1;

            return Carbon::createFromDate($year, $m, $p);
        } catch (\Exception $e) {
            Log::error("[DiaLaboralCalculator] Error calculando Pascua para año {$year}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
