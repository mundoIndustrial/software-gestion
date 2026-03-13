<?php

namespace App\Services;

use App\Models\Festivo;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FestivosService
{
    const CACHE_KEY = 'festivos_colombia';
    const CACHE_TTL = 86400; // 24 horas (suficiente para datos anuales)

    /**
     * Obtiene festivos cacheados para 2 años (actual + próximo)
     * 
     * @return array Fechas de festivos
     */
    public static function obtenerFestivos(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $currentYear = now()->year;
            $nextYear = now()->addYear()->year;

            return Festivo::whereYear('fecha', $currentYear)
                ->orWhereYear('fecha', $nextYear)
                ->pluck('fecha')
                ->toArray();
        });
    }

    /**
     * Invalida el caché de festivos
     * Útil después de agregar nuevos festivos
     */
    public static function invalidarCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Obtiene array de festivos como set para búsqueda O(1)
     * Optimizado para calcularDiasHabiles()
     * 
     * @return array
     */
    public static function obtenerFestivosSet(): array
    {
        $festivosArray = self::obtenerFestivos();
        $festivos = [];
        
        foreach ($festivosArray as $f) {
            try {
                $festivos[Carbon::parse($f)->format('Y-m-d')] = true;
            } catch (\Exception $e) {
                // Ignorar fechas inválidas
            }
        }
        
        return $festivos;
    }
}
