<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use App\Models\Festivo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Servicio de caché inteligente para cálculos de órdenes
 * Optimiza el cálculo de total_de_dias con estrategia de caché multi-nivel
 */
class CacheCalculosService
{
    // Tiempo de vida del caché (1 hora)
    private const CACHE_TTL = 3600;
    
    /**
     * Obtener total de días para una orden con caché inteligente
     */
    public static function getTotalDias($numeroPedido, $estado = null)
    {
        // Generar clave única de caché
        $cacheKey = "orden_dias_{$numeroPedido}_{$estado}";
        
        // Intentar obtener del caché
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // Si no está en caché, calcular
        $dias = self::calcularDiasParaOrden($numeroPedido, $estado);
        
        // Guardar en caché
        Cache::put($cacheKey, $dias, self::CACHE_TTL);
        
        return $dias;
    }
    
    /**
     * Obtener días para múltiples órdenes con caché batch
     * Mucho más rápido que calcular una por una
     */
    public static function getTotalDiasBatch(array $ordenes, array $festivos = [])
    {
        $resultados = [];
        $ordenesACalcular = [];
        
        // Convertir a array asociativo con numero_pedido como clave
        $ordenesIndexadas = [];
        foreach ($ordenes as $orden) {
            $numeroPedido = $orden->numero_pedido ?? $orden['numero_pedido'];
            $ordenesIndexadas[$numeroPedido] = $orden;
        }
        
        // Obtener los que ya están en caché
        foreach ($ordenesIndexadas as $numeroPedido => $orden) {
            $estado = $orden->estado ?? $orden['estado'] ?? 'desconocido';
            $cacheKey = "orden_dias_{$numeroPedido}_{$estado}";
            
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                $resultados[$numeroPedido] = $cached;
            } else {
                // Agregar a lista para calcular
                $ordenesACalcular[$numeroPedido] = $orden;
            }
        }
        
        // Calcular solo los que no estaban en caché
        if (!empty($ordenesACalcular)) {
            if (empty($festivos)) {
                $festivos = Festivo::pluck('fecha')->toArray();
            }
            
            $calculados = self::calcularDiasBatch($ordenesACalcular, $festivos);
            
            // Guardar en caché
            foreach ($calculados as $numeroPedido => $dias) {
                $estado = $ordenesACalcular[$numeroPedido]->estado ?? $ordenesACalcular[$numeroPedido]['estado'] ?? 'desconocido';
                $cacheKey = "orden_dias_{$numeroPedido}_{$estado}";
                Cache::put($cacheKey, $dias, self::CACHE_TTL);
            }
            
            $resultados = array_merge($resultados, $calculados);
        }
        
        return $resultados;
    }
    
    /**
     * Calcular días para una orden específica
     * LÓGICA MEJORADA: 
     * - Si hay ambos procesos (Creación + Despacho): De Creación → Despacho
     * - Si está "No iniciado": De fecha_creacion → HOY
     * - Si está "En Ejecución": De primer_proceso → HOY
     * - Si está "Entregado": De fecha_creacion → último_proceso
     */
    private static function calcularDiasParaOrden($numeroPedido, $estado = null)
    {
        try {
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            
            if (!$orden || !$orden->fecha_de_creacion_de_orden) {
                return 0;
            }
            
            $festivos = Festivo::pluck('fecha')->toArray();
            
            // Pre-calcular festivos
            $festivosSet = [];
            foreach ($festivos as $f) {
                try {
                    $festivosSet[Carbon::parse($f)->format('Y-m-d')] = true;
                } catch (\Exception $e) {}
            }
            
            // PRIMERO: Intentar obtener "Creación de Orden" y "Despacho"
            $procesoCreacion = DB::table('procesos_prenda')
                ->where('numero_pedido', $numeroPedido)
                ->where('proceso', 'Creación de Orden')
                ->select('fecha_inicio')
                ->first();

            $procesoDespacho = DB::table('procesos_prenda')
                ->where('numero_pedido', $numeroPedido)
                ->where('proceso', 'Despacho')
                ->select('fecha_fin')
                ->first();

            // Si tiene AMBOS procesos, calcular entre ellos
            if ($procesoCreacion && $procesoDespacho) {
                $fechaInicio = Carbon::parse($procesoCreacion->fecha_inicio);
                $fechaFin = Carbon::parse($procesoDespacho->fecha_fin);
                return self::calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
            }
            
            // Si NO tiene procesos, usar diferentes lógicas según estado
            $estado = $estado ?? $orden->estado;
            
            if ($estado === 'No iniciado') {
                // De fecha_creacion hasta HOY
                $fechaInicio = Carbon::parse($orden->fecha_de_creacion_de_orden);
                $fechaFin = Carbon::now();
                return self::calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
            }
            
            if ($estado === 'En Ejecución') {
                // De primer proceso hasta HOY
                $primerProceso = DB::table('procesos_prenda')
                    ->where('numero_pedido', $numeroPedido)
                    ->orderBy('fecha_inicio', 'ASC')
                    ->select('fecha_inicio')
                    ->first();
                
                if ($primerProceso) {
                    $fechaInicio = Carbon::parse($primerProceso->fecha_inicio);
                    $fechaFin = Carbon::now();
                    return self::calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
                } else {
                    // Sin procesos pero "En Ejecución", contar desde creación
                    $fechaInicio = Carbon::parse($orden->fecha_de_creacion_de_orden);
                    $fechaFin = Carbon::now();
                    return self::calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
                }
            }
            
            if ($estado === 'Entregado') {
                // De fecha_creacion hasta último proceso
                $ultimoProceso = DB::table('procesos_prenda')
                    ->where('numero_pedido', $numeroPedido)
                    ->orderBy('fecha_fin', 'DESC')
                    ->select('fecha_fin')
                    ->first();
                
                if ($ultimoProceso && $ultimoProceso->fecha_fin) {
                    $fechaInicio = Carbon::parse($orden->fecha_de_creacion_de_orden);
                    $fechaFin = Carbon::parse($ultimoProceso->fecha_fin);
                    return self::calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
                }
            }
            
            // Fallback: De fecha creacion hasta HOY
            $fechaInicio = Carbon::parse($orden->fecha_de_creacion_de_orden);
            $fechaFin = Carbon::now();
            return self::calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
            
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Calcular días para batch de órdenes
     * LÓGICA MEJORADA: Soporta órdenes sin procesos
     */
    private static function calcularDiasBatch(array $ordenes, array $festivos): array
    {
        $resultados = [];
        
        // Pre-calcular set de festivos
        $festivosSet = [];
        foreach ($festivos as $f) {
            try {
                $festivosSet[Carbon::parse($f)->format('Y-m-d')] = true;
            } catch (\Exception $e) {
                // Ignorar inválidos
            }
        }
        
        foreach ($ordenes as $numeroPedido => $orden) {
            $fechaCreacion = null;
            $estado = 'desconocido';
            
            // Obtener valores de forma segura (puede ser objeto o array)
            if (is_object($orden)) {
                $fechaCreacion = $orden->fecha_de_creacion_de_orden ?? null;
                $estado = $orden->estado ?? 'desconocido';
            } elseif (is_array($orden)) {
                $fechaCreacion = $orden['fecha_de_creacion_de_orden'] ?? null;
                $estado = $orden['estado'] ?? 'desconocido';
            }
            
            if (!$fechaCreacion) {
                $resultados[$numeroPedido] = 0;
                continue;
            }
            
            try {
                // PRIMERO: Intentar obtener "Creación de Orden" y "Despacho"
                $procesoCreacion = DB::table('procesos_prenda')
                    ->where('numero_pedido', $numeroPedido)
                    ->where('proceso', 'Creación de Orden')
                    ->select('fecha_inicio')
                    ->first();

                $procesoDespacho = DB::table('procesos_prenda')
                    ->where('numero_pedido', $numeroPedido)
                    ->where('proceso', 'Despacho')
                    ->select('fecha_fin')
                    ->first();

                // Si tiene AMBOS procesos, calcular entre ellos
                if ($procesoCreacion && $procesoDespacho) {
                    $fechaInicio = Carbon::parse($procesoCreacion->fecha_inicio);
                    $fechaFin = Carbon::parse($procesoDespacho->fecha_fin);
                    $dias = self::calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
                    $resultados[$numeroPedido] = max(0, $dias);
                    continue;
                }
                
                // Si NO tiene procesos, calcular basado en estado
                if ($estado === 'No iniciado') {
                    // De fecha_creacion hasta HOY
                    $fechaInicio = Carbon::parse($fechaCreacion);
                    $fechaFin = Carbon::now();
                    $dias = self::calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
                    $resultados[$numeroPedido] = max(0, $dias);
                } elseif ($estado === 'En Ejecución') {
                    // De primer proceso hasta HOY (o desde creación si sin procesos)
                    $primerProceso = DB::table('procesos_prenda')
                        ->where('numero_pedido', $numeroPedido)
                        ->orderBy('fecha_inicio', 'ASC')
                        ->select('fecha_inicio')
                        ->first();
                    
                    if ($primerProceso) {
                        $fechaInicio = Carbon::parse($primerProceso->fecha_inicio);
                    } else {
                        $fechaInicio = Carbon::parse($fechaCreacion);
                    }
                    $fechaFin = Carbon::now();
                    $dias = self::calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
                    $resultados[$numeroPedido] = max(0, $dias);
                } elseif ($estado === 'Entregado') {
                    // De fecha_creacion hasta último proceso (o hasta hoy si sin procesos)
                    $ultimoProceso = DB::table('procesos_prenda')
                        ->where('numero_pedido', $numeroPedido)
                        ->orderBy('fecha_fin', 'DESC')
                        ->select('fecha_fin')
                        ->first();
                    
                    if ($ultimoProceso && $ultimoProceso->fecha_fin) {
                        $fechaFin = Carbon::parse($ultimoProceso->fecha_fin);
                    } else {
                        $fechaFin = Carbon::now();
                    }
                    $fechaInicio = Carbon::parse($fechaCreacion);
                    $dias = self::calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
                    $resultados[$numeroPedido] = max(0, $dias);
                } else {
                    // Fallback: De fecha creacion hasta HOY
                    $fechaInicio = Carbon::parse($fechaCreacion);
                    $fechaFin = Carbon::now();
                    $dias = self::calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
                    $resultados[$numeroPedido] = max(0, $dias);
                }
                
            } catch (\Exception $e) {
                // En caso de error, retornar 0
                $resultados[$numeroPedido] = 0;
            }
        }
        
        return $resultados;
    }
    
    /**
     * Calcular días hábiles optimizado
     */
    private static function calcularDiasHabiles(Carbon $inicio, Carbon $fin, $festivosSet): int
    {
        // Si festivosSet es array indexado por string, usarlo directo
        // Si es array numérico, convertir
        if (!empty($festivosSet) && !isset($festivosSet[array_key_first($festivosSet)])) {
            $temp = [];
            foreach ($festivosSet as $f) {
                $temp[Carbon::parse($f)->format('Y-m-d')] = true;
            }
            $festivosSet = $temp;
        }
        
        $current = $inicio->copy()->addDay();
        $totalDays = 0;
        $weekends = 0;
        $holidays = 0;
        
        $maxIterations = 3650;
        $iterations = 0;
        
        while ($current <= $fin && $iterations < $maxIterations) {
            $dateString = $current->format('Y-m-d');
            $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
            $isFestivo = isset($festivosSet[$dateString]);
            
            $totalDays++;
            if ($isWeekend) $weekends++;
            if ($isFestivo) $holidays++;
            
            $current->addDay();
            $iterations++;
        }
        
        return $totalDays - $weekends - $holidays;
    }
    
    /**
     * Invalidar caché para una orden
     * Se llama cuando se actualiza una orden
     */
    public static function invalidarOrden($numeroPedido)
    {
        // Invalidar todos los estados posibles
        $estados = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];
        foreach ($estados as $estado) {
            $cacheKey = "orden_dias_{$numeroPedido}_{$estado}";
            Cache::forget($cacheKey);
        }
    }
    
    /**
     * Invalidar caché de todas las órdenes
     * Usar con cuidado - solo en migraciones o cambios masivos
     */
    public static function invalidarTodo()
    {
        Cache::tags(['ordenes_dias'])->flush();
    }
    
    /**
     * Precalcular días para todas las órdenes
     * Ejecutar cada hora mediante un Job
     */
    public static function precalcularTodo()
    {
        $ordenes = PedidoProduccion::all();
        $festivos = Festivo::pluck('fecha')->toArray();
        
        foreach ($ordenes as $orden) {
            $dias = self::calcularDiasParaOrden($orden->numero_pedido, $orden->estado);
            $cacheKey = "orden_dias_{$orden->numero_pedido}_{$orden->estado}";
            Cache::put($cacheKey, $dias, self::CACHE_TTL);
        }
        
        return count($ordenes);
    }
    
    /**
     * Estadísticas de caché
     */
    public static function getStats()
    {
        // Contar órdenes en caché
        $ordenes = PedidoProduccion::count();
        $cacheSize = 0;
        
        try {
            // Aproximar tamaño del caché
            $sample = PedidoProduccion::first();
            if ($sample) {
                $testKey = "test_cache_size_" . time();
                Cache::put($testKey, str_repeat('x', 1000), 60);
                $cacheSize = 1000 * $ordenes; // Aproximación cruda
                Cache::forget($testKey);
            }
        } catch (\Exception $e) {
            // Ignorar errores de estimación
        }
        
        return [
            'total_ordenes' => $ordenes,
            'cache_size_estimated_bytes' => $cacheSize,
            'cache_size_mb' => round($cacheSize / 1024 / 1024, 2),
            'ttl_seconds' => self::CACHE_TTL,
        ];
    }
}
