<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FestivosColombiaService
{
    /**
     * Obtener festivos de Colombia para un año específico
     * SOLO desde la API pública - SIN FALLBACKS
     * Si la API falla, lanza excepción
     */
    public static function obtenerFestivos(int $year = null): array
    {
        $year = $year ?? now()->year;
        
        // Cachear por 30 días
        return Cache::remember("festivos_colombia_{$year}", 2592000, function () use ($year) {
            // Intento 1: api.nager.date
            try {
                $response = Http::timeout(5)
                    ->withoutVerifying()
                    ->withHeaders(['User-Agent' => 'Laravel-App/1.0'])
                    ->get("https://api.nager.date/v3/PublicHolidays/{$year}/CO");
                
                if ($response->successful()) {
                    $festivos = $response->json();
                    return collect($festivos)->pluck('date')->toArray();
                }
            } catch (\Exception $e) {
                \Log::debug("Endpoint api.nager.date falló para {$year}: " . $e->getMessage());
            }

            // Intento 2: date.nager.at
            try {
                $response = Http::timeout(5)
                    ->withoutVerifying()
                    ->withHeaders(['User-Agent' => 'Laravel-App/1.0'])
                    ->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/CO");
                
                if ($response->successful()) {
                    $festivos = $response->json();
                    return collect($festivos)->pluck('date')->toArray();
                }
            } catch (\Exception $e) {
                \Log::debug("Endpoint date.nager.at falló para {$year}: " . $e->getMessage());
            }

            // AMBOS FALLARON - Lanzar excepción
            throw new \Exception("No se pudieron obtener festivos de la API para el año {$year}. Ambos endpoints de Nager.Date fallaron.");
        });
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
