<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Cache;
use App\Services\FestivosColombiaService;
use Carbon\Carbon;

/**
 * RegistroOrdenCacheService
 * 
 * Responsabilidad: Gestionar invalidación de caché para órdenes
 * Extrae la lógica de caché del controlador
 * 
 * CUMPLE SRP: Solo maneja caché
 */
class RegistroOrdenCacheService
{
    /**
     * Invalidar caché de días calculados para una orden específica
     * Se ejecuta cuando se actualiza o elimina una orden
     * 
     * @param int $pedido - Número de pedido
     * @return void
     */
    public function invalidateDaysCache(int $pedido): void
    {
        $hoy = now()->format('Y-m-d');
        
        // Obtener festivos del servicio automático (no de BD)
        $currentYear = now()->year;
        $festivos = FestivosColombiaService::obtenerFestivos($currentYear);
        $festivosCacheKey = md5(serialize($festivos));
        
        // Invalidar para todos los posibles estados
        $estados = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];
        
        foreach ($estados as $estado) {
            $cacheKey = "orden_dias_{$pedido}_{$estado}_{$hoy}_{$festivosCacheKey}";
            Cache::forget($cacheKey);
        }
        
        // También invalidar para días anteriores (últimos 7 días)
        for ($i = 1; $i <= 7; $i++) {
            $fecha = now()->subDays($i)->format('Y-m-d');
            foreach ($estados as $estado) {
                $cacheKey = "orden_dias_{$pedido}_{$estado}_{$fecha}_{$festivosCacheKey}";
                Cache::forget($cacheKey);
            }
        }
    }

    /**
     * Invalidar caché para múltiples órdenes
     * 
     * @param array $pedidos - Array de números de pedido
     * @return void
     */
    public function invalidateMultipleDaysCache(array $pedidos): void
    {
        foreach ($pedidos as $pedido) {
            $this->invalidateDaysCache($pedido);
        }
    }

    /**
     * Limpiar TODO el caché de órdenes (usar con cuidado)
     * Útil para migraciones o correcciones masivas
     * 
     * @return void
     */
    public function flushAllOrdersCache(): void
    {
        // Flush tags o patrones si están disponibles
        // Por ahora usar una aproximación simple
        Cache::flush();
    }
}
