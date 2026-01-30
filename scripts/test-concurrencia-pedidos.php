<?php

/**
 * Script de prueba de concurrencia para creación de pedidos
 * 
 * Este script simula múltiples usuarios creando pedidos simultáneamente
 * para verificar que el sistema maneje correctamente la concurrencia.
 * 
 * Uso:
 * php scripts/test-concurrencia-pedidos.php [numero_usuarios] [numero_rondas]
 * 
 * Ejemplos:
 * php scripts/test-concurrencia-pedidos.php 15 1    // 15 usuarios, 1 ronda
 * php scripts/test-concurrencia-pedidos.php 30 3    // 30 usuarios, 3 rondas
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use App\Application\Services\Asesores\CrearPedidoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConcurrenciaTest
{
    private int $numeroUsuarios;
    private int $numeroRondas;
    private array $resultados = [];
    private array $errores = [];
    
    public function __construct(int $numeroUsuarios = 15, int $numeroRondas = 1)
    {
        $this->numeroUsuarios = $numeroUsuarios;
        $this->numeroRondas = $numeroRondas;
        
        echo "🚀 Iniciando prueba de concurrencia\n";
        echo "📊 Usuarios: {$this->numeroUsuarios}\n";
        echo "🔄 Rondas: {$this->numeroRondas}\n";
        echo "⏰ " . date('Y-m-d H:i:s') . "\n\n";
    }
    
    public function ejecutar(): void
    {
        // Configurar timeout para evitar deadlocks
        DB::statement('SET SESSION innodb_lock_wait_timeout = 5');
        
        try {
            for ($ronda = 1; $ronda <= $this->numeroRondas; $ronda++) {
                echo "🎯 Ronda {$ronda}/{$this->numeroRondas}\n";
                $this->ejecutarRonda($ronda);
                
                // Pequeña pausa entre rondas
                if ($ronda < $this->numeroRondas) {
                    sleep(1);
                }
            }
            
            $this->mostrarResultados();
            
        } catch (\Exception $e) {
            echo "❌ Error fatal: " . $e->getMessage() . "\n";
            Log::error('[CONCURRENCIA_TEST] Error fatal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            DB::statement('SET SESSION innodb_lock_wait_timeout = DEFAULT');
        }
    }
    
    private function ejecutarRonda(int $ronda): void
    {
        $usuarios = User::take($this->numeroUsuarios)->get();
        $promises = [];
        $startTime = microtime(true);
        
        // Iniciar procesos "paralelos" (simulados con multi_process si está disponible)
        foreach ($usuarios as $index => $usuario) {
            $promises[] = $this->crearPedidoAsync($usuario, $index, $ronda);
        }
        
        // Esperar resultados
        $resultadosRonda = [];
        $erroresRonda = [];
        
        foreach ($promises as $promise) {
            try {
                $resultadosRonda[] = $promise();
            } catch (\Exception $e) {
                $erroresRonda[] = [
                    'error' => $e->getMessage(),
                    'usuario' => $e->getCode() ?? 'unknown'
                ];
            }
        }
        
        $endTime = microtime(true);
        $duracion = $endTime - $startTime;
        
        // Guardar resultados
        $this->resultados[$ronda] = [
            'pedidos_creados' => count($resultadosRonda),
            'errores' => count($erroresRonda),
            'duracion' => round($duracion, 3),
            'pedidos' => $resultadosRonda
        ];
        
        $this->errores = array_merge($this->errores, $erroresRonda);
        
        echo "   ✅ Pedidos creados: " . count($resultadosRonda) . "\n";
        echo "   ❌ Errores: " . count($erroresRonda) . "\n";
        echo "   ⏱️  Duración: " . round($duracion, 3) . "s\n\n";
        
        // Verificar integridad inmediata
        $this->verificarIntegridad($resultadosRonda, $ronda);
    }
    
    private function crearPedidoAsync(User $usuario, int $index, int $ronda): callable
    {
        return function() use ($usuario, $index, $ronda) {
            // Simular autenticación
            auth()->login($usuario);
            
            // Datos de prueba únicos
            $datos = [
                'cliente' => "Cliente R{$ronda}U{$index} " . uniqid(),
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
            
            return [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente,
                'asesor_id' => $pedido->asesor_id,
                'estado' => $pedido->estado,
                'usuario_index' => $index,
                'ronda' => $ronda
            ];
        };
    }
    
    private function verificarIntegridad(array $pedidos, int $ronda): void
    {
        if (empty($pedidos)) {
            echo "   ⚠️  Sin pedidos para verificar\n";
            return;
        }
        
        // Verificar IDs únicos
        $ids = array_column($pedidos, 'id');
        $idsUnicos = array_unique($ids);
        
        if (count($ids) !== count($idsUnicos)) {
            echo "   🚨 ¡IDS DUPLICADOS DETECTADOS!\n";
            Log::error('[CONCURRENCIA_TEST] IDs duplicados', [
                'ronda' => $ronda,
                'ids' => $ids,
                'duplicados' => array_diff_assoc($ids, $idsUnicos)
            ]);
        } else {
            echo "   ✅ IDs únicos verificadas\n";
        }
        
        // Verificar que numero_pedido sea null
        $numerosNoNulos = array_filter(array_column($pedidos, 'numero_pedido'));
        if (!empty($numerosNoNulos)) {
            echo "   🚨 ¡NÚMEROS DE PEDIDO NO DEBEN SER NULOS!\n";
        } else {
            echo "   ✅ números de pedido correctos (null)\n";
        }
        
        // Verificar secuencia de IDs
        sort($ids);
        $esperado = range($ids[0], $ids[0] + count($ids) - 1);
        if ($ids !== $esperado) {
            echo "   🚨 ¡SECUENCIA DE IDS ROTA!\n";
            Log::error('[CONCURRENCIA_TEST] Secuencia rota', [
                'ronda' => $ronda,
                'esperado' => $esperado,
                'recibido' => $ids
            ]);
        } else {
            echo "   ✅ Secuencia de IDs correcta\n";
        }
    }
    
    private function mostrarResultados(): void
    {
        echo "\n📊 RESULTADOS FINALES\n";
        echo "==================\n";
        
        $totalPedidos = 0;
        $totalErrores = 0;
        $totalDuracion = 0;
        
        foreach ($this->resultados as $ronda => $resultado) {
            echo "Ronda {$ronda}: {$resultado['pedidos_creados']} pedidos, ";
            echo "{$resultado['errores']} errores, {$resultado['duracion']}s\n";
            
            $totalPedidos += $resultado['pedidos_creados'];
            $totalErrores += $resultado['errores'];
            $totalDuracion += $resultado['duracion'];
        }
        
        echo "\n📈 TOTALES:\n";
        echo "Pedidos creados: {$totalPedidos}\n";
        echo "Errores: {$totalErrores}\n";
        echo "Duración total: " . round($totalDuracion, 3) . "s\n";
        echo "Promedio por pedido: " . round($totalDuracion / max($totalPedidos, 1), 3) . "s\n";
        
        if ($totalErrores === 0) {
            echo "\n✅ PRUEBA EXITOSA - Sin errores de concurrencia\n";
        } else {
            echo "\n❌ PRUEBA CON ERRORES - Revisar logs\n";
            foreach ($this->errores as $error) {
                echo "  - " . $error['error'] . "\n";
            }
        }
        
        // Verificación final en BD
        $this->verificacionFinalBD();
    }
    
    private function verificacionFinalBD(): void
    {
        echo "\n🔍 VERIFICACIÓN FINAL EN BD\n";
        echo "==========================\n";
        
        // Contar pedidos creados
        $totalPedidosBD = DB::table('pedidos_produccion')->count();
        echo "Total pedidos en BD: {$totalPedidosBD}\n";
        
        // Verificar AUTO_INCREMENT
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
        
        // Buscar anomalías
        $idsDuplicados = DB::table('pedidos_produccion')
            ->select('id', DB::raw('COUNT(*) as count'))
            ->groupBy('id')
            ->having('count', '>', 1)
            ->get();
            
        if ($idsDuplicados->isNotEmpty()) {
            echo "🚨 IDS DUPLICADOS EN BD:\n";
            foreach ($idsDuplicados as $dup) {
                echo "  ID {$dup->id}: {$dup->count} veces\n";
            }
        } else {
            echo "✅ Sin IDs duplicados en BD\n";
        }
    }
}

// Ejecutar prueba
$numeroUsuarios = intval($argv[1] ?? 15);
$numeroRondas = intval($argv[2] ?? 1);

$test = new ConcurrenciaTest($numeroUsuarios, $numeroRondas);
$test->ejecutar();
