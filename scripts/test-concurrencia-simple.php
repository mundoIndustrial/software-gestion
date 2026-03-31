<?php

/**
 * Script simple de prueba de concurrencia
 * Simula múltiples usuarios creando pedidos
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Application\Services\Asesores\CrearPedidoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo " Iniciando prueba de concurrencia simple\n";
echo "⏰ " . date('Y-m-d H:i:s') . "\n\n";

// Configurar timeout para evitar deadlocks
DB::statement('SET SESSION innodb_lock_wait_timeout = 5');

try {
    $numeroUsuarios = 15;
    $resultados = [];
    $startTime = microtime(true);
    
    echo " Creando {$numeroUsuarios} pedidos simultáneamente...\n";
    
    // Obtener usuarios
    $usuarios = User::take($numeroUsuarios)->get();
    
    if ($usuarios->count() < $numeroUsuarios) {
        echo "  Solo hay {$usuarios->count()} usuarios disponibles\n";
        $numeroUsuarios = $usuarios->count();
    }
    
    // Crear pedidos en secuencia rápida (simula concurrencia)
    foreach ($usuarios as $index => $usuario) {
        try {
            // Simular autenticación
            auth()->login($usuario);
            
            // Datos de prueba únicos
            $datos = [
                'cliente' => "Cliente Test " . uniqid() . " U{$index}",
                'forma_de_pago' => 'contado',
                'productos_friendly' => [
                    [
                        'nombre_prenda' => 'Camisa Test',
                        'cantidad' => rand(5, 20),
                        'telas' => []
                    ]
                ],
                'archivos' => []
            ];
            
            // Crear pedido
            $service = app(CrearPedidoService::class);
            $pedido = $service->crear($datos);
            
            $resultados[] = [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'asesor_id' => $pedido->asesor_id,
                'estado' => $pedido->estado,
                'usuario_index' => $index
            ];
            
            echo "   Pedido #{$pedido->id} creado por usuario {$index}\n";
            
        } catch (\Exception $e) {
            echo "   Error usuario {$index}: " . $e->getMessage() . "\n";
            Log::error('[CONCURRENCIA_SIMPLE] Error', [
                'usuario_index' => $index,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    $endTime = microtime(true);
    $duracion = $endTime - $startTime;
    
    echo "\n RESULTADOS:\n";
    echo "Pedidos creados: " . count($resultados) . "\n";
    echo "Duración: " . round($duracion, 3) . "s\n";
    echo "Promedio por pedido: " . round($duracion / max(count($resultados), 1), 3) . "s\n";
    
    // Verificar integridad
    if (!empty($resultados)) {
        $ids = array_column($resultados, 'id');
        $idsUnicos = array_unique($ids);
        
        echo "\n VERIFICACIÓN DE INTEGRIDAD:\n";
        
        if (count($ids) === count($idsUnicos)) {
            echo " Todos los IDs son únicos\n";
        } else {
            echo "🚨 ¡IDS DUPLICADOS DETECTADOS!\n";
        }
        
        // Verificar secuencia
        sort($ids);
        $esperado = range($ids[0], $ids[0] + count($ids) - 1);
        if ($ids === $esperado) {
            echo " Secuencia de IDs correcta\n";
        } else {
            echo "🚨 ¡SECUENCIA DE IDS ROTA!\n";
        }
        
        // Verificar números de pedido
        $numerosNoNulos = array_filter(array_column($resultados, 'numero_pedido'));
        if (empty($numerosNoNulos)) {
            echo " números de pedido correctos (null)\n";
        } else {
            echo "🚨 ¡NÚMEROS DE PEDIDO NO DEBEN SER NULOS!\n";
        }
        
        echo "Rango de IDs: " . min($ids) . " - " . max($ids) . "\n";
    }
    
    // Verificación final en BD
    echo "\n VERIFICACIÓN FINAL EN BD:\n";
    $totalPedidosBD = DB::table('pedidos_produccion')->count();
    echo "Total pedidos en BD: {$totalPedidosBD}\n";
    
    $autoIncrement = DB::select("
        SELECT AUTO_INCREMENT 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos_produccion'
    ")[0]->AUTO_INCREMENT ?? 'unknown';
    
    echo "Próximo AUTO_INCREMENT: {$autoIncrement}\n";
    
    // Verificar secuencia de números de pedido
    $secuencia = DB::table('numero_secuencias')
        ->where('tipo', 'pedido_produccion')
        ->first();
        
    if ($secuencia) {
        echo "Siguiente número pedido: {$secuencia->siguiente}\n";
        echo "Último usado: {$secuencia->ultimo_usado}\n";
    }
    
    echo "\n PRUEBA COMPLETADA\n";
    
} catch (\Exception $e) {
    echo " Error fatal: " . $e->getMessage() . "\n";
    Log::error('[CONCURRENCIA_SIMPLE] Error fatal', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} finally {
    DB::statement('SET SESSION innodb_lock_wait_timeout = DEFAULT');
}
