<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FestivosColombiaService
{
    /**
     * Obtener festivos de Colombia para un año específico
     * Usa API pública y cachea por 30 días
     */
    public static function obtenerFestivos(int $year = null): array
    {
        $year = $year ?? now()->year;
        
        // Cachear por 30 días (los festivos no cambian durante el año)
        return Cache::remember("festivos_colombia_{$year}", 2592000, function () use ($year) {
            try {
                // API pública de festivos por país
                $response = Http::timeout(5)->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/CO");
                
                if ($response->successful()) {
                    $festivos = $response->json();
                    
                    // Extraer solo las fechas
                    return collect($festivos)->pluck('date')->toArray();
                }
                
                // Si falla la API, usar festivos hardcodeados como fallback
                return self::festivosColombia2025Fallback();
                
            } catch (\Exception $e) {
                \Log::warning("Error obteniendo festivos de API: " . $e->getMessage());
                return self::festivosColombia2025Fallback();
            }
        });
    }
    
    /**
     * Festivos de Colombia 2025 como fallback
     * Incluye Ley Emiliani (festivos trasladados al lunes)
     */
    private static function festivosColombia2025Fallback(): array
    {
        return [
            '2025-01-01', // Año Nuevo
            '2025-01-06', // Reyes Magos (trasladado al lunes)
            '2025-03-24', // San José (trasladado al lunes)
            '2025-04-17', // Jueves Santo
            '2025-04-18', // Viernes Santo
            '2025-05-01', // Día del Trabajo
            '2025-06-02', // Ascensión (trasladado al lunes)
            '2025-06-23', // Corpus Christi (trasladado al lunes)
            '2025-06-30', // Sagrado Corazón (trasladado al lunes)
            '2025-07-07', // San Pedro y San Pablo (trasladado al lunes)
            '2025-07-20', // Día de la Independencia
            '2025-08-07', // Batalla de Boyacá
            '2025-08-18', // Asunción (trasladado al lunes)
            '2025-10-13', // Día de la Raza (trasladado al lunes)
            '2025-11-03', // Todos los Santos (trasladado al lunes)
            '2025-11-17', // Independencia de Cartagena (trasladado al lunes)
            '2025-12-08', // Inmaculada Concepción
            '2025-12-25', // Navidad
        ];
    }
    
    /**
     * Verificar si una fecha es festivo en Colombia
     */
    public static function esFestivo(string $fecha): bool
    {
        $carbon = Carbon::parse($fecha);
        $festivos = self::obtenerFestivos($carbon->year);
        
        return in_array($carbon->format('Y-m-d'), $festivos);
    }
    
    /**
     * Obtener festivos entre dos fechas
     */
    public static function festivosEnRango(Carbon $inicio, Carbon $fin): array
    {
        $festivos = [];
        
        // Obtener festivos de todos los años en el rango
        for ($year = $inicio->year; $year <= $fin->year; $year++) {
            $festivosYear = self::obtenerFestivos($year);
            $festivos = array_merge($festivos, $festivosYear);
        }
        
        // Filtrar solo los que están en el rango
        return array_filter($festivos, function ($festivo) use ($inicio, $fin) {
            $fecha = Carbon::parse($festivo);
            return $fecha->between($inicio, $fin);
        });
    }
}
