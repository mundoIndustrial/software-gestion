<?php

namespace App\Application\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DiaLaboralCalculator - Multi-API con fallback
 * 
 * Responsabilidad: Calcular días laborales (excluyendo fines de semana y festivos)
 * 
 * Estrategia de APIs:
 * 1. Intenta con Nager.Date (api.nager.date)
 * 2. Si falla, intenta con nager.date (dominio base)
 * 3. Si falla, intenta con Abstract API
 * 4. Si falla, usa cálculo local de festivos
 */
class DiaLaboralCalculatorMultiAPI
{
    private const NAGER_TIMEOUT = 5;
    private const ABSTRACT_API_TIMEOUT = 5;

    /**
     * Calcular días laborales desde la creación de la orden hasta ahora
     * Excluye: sábados, domingos y días festivos
     * Solo cuenta: lunes a viernes (días laborales)
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

            if ($fechaInicio->isAfter($fechaFin)) {
                return 0;
            }

            $festivosSet = $this->obtenerFestivosDelRango($fechaInicio, $fechaFin);

            $current = $fechaInicio->copy()->addDay();
            $totalDays = 0;

            while ($current <= $fechaFin) {
                $dateString = $current->format('Y-m-d');
                $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                $isFestivo = isset($festivosSet[$dateString]);

                if (!$isWeekend && !$isFestivo) {
                    $totalDays++;
                }

                $current->addDay();
            }

            Log::info("[DiaLaboralCalculator] Cálculo final: {dias} días laborales", [
                'dias' => $totalDays,
                'inicio' => $fechaInicio->format('Y-m-d'),
                'fin' => $fechaFin->format('Y-m-d'),
                'festivos_encontrados' => count($festivosSet)
            ]);

            return max(0, $totalDays);
        } catch (\Exception $e) {
            Log::error('[DiaLaboralCalculator] Error calculando días laborales: ' . $e->getMessage());
            return max(0, $fechaInicio->diffInDays($fechaFin));
        }
    }

    /**
     * Obtener festivos del rango (múltiples años)
     */
    private function obtenerFestivosDelRango(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        try {
            $festivos = [];
            $currentYear = $fechaInicio->year;
            $endYear = $fechaFin->year;

            for ($year = $currentYear; $year <= $endYear; $year++) {
                $festivosDelAnio = $this->obtenerFestivosDelAnio($year);
                $festivos = array_merge($festivos, $festivosDelAnio);
            }

            return $festivos;
        } catch (\Exception $e) {
            Log::warning('[DiaLaboralCalculator] Error en obtenerFestivosDelRango: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener festivos de un año específico
     * Intenta múltiples APIs en orden de preferencia
     */
    private function obtenerFestivosDelAnio(int $year): array
    {
        // Intenta Nager.Date (api.nager.date)
        $festivos = $this->intentarNagerDateAPI($year);
        if (!empty($festivos)) {
            Log::info("[DiaLaboralCalculator] ✅ Nager.Date API (api.nager.date) funcionó para {year}", ['year' => $year]);
            return $festivos;
        }

        // Intenta Nager.Date alternativo (nager.date)
        $festivos = $this->intentarNagerDateAlternativo($year);
        if (!empty($festivos)) {
            Log::info("[DiaLaboralCalculator] ✅ Nager.Date alternativo (nager.date) funcionó para {year}", ['year' => $year]);
            return $festivos;
        }

        // Intenta Abstract API
        $festivos = $this->intentarAbstractAPI($year);
        if (!empty($festivos)) {
            Log::info("[DiaLaboralCalculator] ✅ Abstract API funcionó para {year}", ['year' => $year]);
            return $festivos;
        }

        // Fallback: cálculo local
        Log::warning("[DiaLaboralCalculator] ⚠️ Todas las APIs fallaron, usando cálculo local para {year}", ['year' => $year]);
        return $this->obtenerFestivosLocales($year);
    }

    /**
     * Intentar con Nager.Date usando api.nager.date
     */
    private function intentarNagerDateAPI(int $year): array
    {
        try {
            $response = Http::timeout(self::NAGER_TIMEOUT)
                ->withoutVerifying()
                ->withHeaders(['User-Agent' => 'Laravel-App/1.0'])
                ->get("https://api.nager.date/v3/PublicHolidays/{$year}/CO");

            if ($response->successful() && is_array($response->json())) {
                return $this->procesarRespuestaNagerDate($response->json());
            }
        } catch (\Exception $e) {
            Log::debug("[DiaLaboralCalculator] api.nager.date falló: " . $e->getMessage());
        }
        return [];
    }

    /**
     * Intentar con Nager.Date alternativo (nager.date sin "api.")
     * Accede a través del dominio base
     */
    private function intentarNagerDateAlternativo(int $year): array
    {
        try {
            $response = Http::timeout(self::NAGER_TIMEOUT)
                ->withoutVerifying()
                ->withHeaders(['User-Agent' => 'Laravel-App/1.0'])
                ->get("https://nager.date/api/v3/PublicHolidays/{$year}/CO");

            if ($response->successful() && is_array($response->json())) {
                return $this->procesarRespuestaNagerDate($response->json());
            }
        } catch (\Exception $e) {
            Log::debug("[DiaLaboralCalculator] nager.date alternativo falló: " . $e->getMessage());
        }
        return [];
    }

    /**
     * Intentar con Abstract API (fallback de calidad)
     * Requiere API key gratuita de https://www.abstractapi.com/holidays-api
     * La clave puede ser obtenida gratuitamente del config
     */
    private function intentarAbstractAPI(int $year): array
    {
        try {
            $apiKey = config('services.abstract_api_key', ''); // Puedes agregar a config si lo deseas
            
            if (empty($apiKey)) {
                return [];
            }

            $response = Http::timeout(self::ABSTRACT_API_TIMEOUT)
                ->withoutVerifying()
                ->get("https://api.abstractapi.com/v1/holidays", [
                    'api_key' => $apiKey,
                    'country_iso' => 'CO',
                    'year' => $year
                ]);

            if ($response->successful() && is_array($response->json())) {
                return $this->procesarRespuestaAbstractAPI($response->json());
            }
        } catch (\Exception $e) {
            Log::debug("[DiaLaboralCalculator] Abstract API falló: " . $e->getMessage());
        }
        return [];
    }

    /**
     * Procesar respuesta de Nager.Date
     */
    private function procesarRespuestaNagerDate(array $data): array
    {
        $festivos = [];
        foreach ($data as $item) {
            if (isset($item['date'])) {
                $festivos[$item['date']] = true;
            }
        }
        return $festivos;
    }

    /**
     * Procesar respuesta de Abstract API
     */
    private function procesarRespuestaAbstractAPI(array $data): array
    {
        $festivos = [];
        foreach ($data as $item) {
            if (isset($item['date'])) {
                $festivos[$item['date']] = true;
            }
        }
        return $festivos;
    }

    /**
     * Festivos locales de Colombia como fallback
     */
    private function obtenerFestivosLocales(int $year): array
    {
        $festivosLocales = [
            "{$year}-01-01",
            "{$year}-01-06",
            "{$year}-03-19",
            "{$year}-05-01",
            "{$year}-06-17",
            "{$year}-07-01",
            "{$year}-08-07",
            "{$year}-08-15",
            "{$year}-11-02",
            "{$year}-12-08",
            "{$year}-12-25",
        ];

        $festivosMoviles = $this->calcularFestivosMoviles($year);
        $festivos = [];

        foreach (array_merge($festivosLocales, $festivosMoviles) as $fecha) {
            $festivos[$fecha] = true;
        }

        Log::debug("[DiaLaboralCalculator] Usando festivos locales para año {$year}");
        return $festivos;
    }

    /**
     * Calcular festivos móviles basados en Pascua
     */
    private function calcularFestivosMoviles(int $year): array
    {
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
     * Algoritmo de Computus para calcular Pascua
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
            Log::error("[DiaLaboralCalculator] Error calculando Pascua para año {$year}");
            return null;
        }
    }
}
