<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Application\Services\Asesores\CrearPedidoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestConcurrenciaPedidos extends Command
{
    protected $signature = 'pedidos:test-concurrencia {usuarios=15} {rondas=1}';
    protected $description = 'Probar concurrencia en creación de pedidos';

    public function handle()
    {
        $numeroUsuarios = (int) $this->argument('usuarios');
        $numeroRondas = (int) $this->argument('rondas');
        
        $this->info("🚀 Iniciando prueba de concurrencia");
        $this->info("📊 Usuarios: {$numeroUsuarios}");
        $this->info("🔄 Rondas: {$numeroRondas}");
        $this->info("⏰ " . date('Y-m-d H:i:s'));
        
        // Configurar timeout para evitar deadlocks
        DB::statement('SET SESSION innodb_lock_wait_timeout = 5');
        
        try {
            $totalPedidos = 0;
            $totalErrores = 0;
            $startTimeTotal = microtime(true);
            
            for ($ronda = 1; $ronda <= $numeroRondas; $ronda++) {
                $this->line("\n🎯 Ronda {$ronda}/{$numeroRondas}");
                
                $resultados = $this->ejecutarRonda($numeroUsuarios, $ronda);
                
                $totalPedidos += $resultados['pedidos'];
                $totalErrores += $resultados['errores'];
                
                $this->info("   ✅ Pedidos creados: {$resultados['pedidos']}");
                $this->info("   ❌ Errores: {$resultados['errores']}");
                $this->info("   ⏱️  Duración: {$resultados['duracion']}s");
                
                // Pequeña pausa entre rondas
                if ($ronda < $numeroRondas) {
                    sleep(1);
                }
            }
            
            $endTimeTotal = microtime(true);
            $duracionTotal = $endTimeTotal - $startTimeTotal;
            
            $this->mostrarResultadosFinales($totalPedidos, $totalErrores, $duracionTotal);
            
        } catch (\Exception $e) {
            $this->error("❌ Error fatal: " . $e->getMessage());
            Log::error('[CONCURRENCIA_COMMAND] Error fatal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            DB::statement('SET SESSION innodb_lock_wait_timeout = DEFAULT');
        }
        
        return $totalErrores === 0 ? 0 : 1;
    }
    
    private function ejecutarRonda(int $numeroUsuarios, int $ronda): array
    {
        $usuarios = User::take($numeroUsuarios)->get();
        $resultados = [];
        $errores = 0;
        $startTime = microtime(true);
        
        if ($usuarios->count() < $numeroUsuarios) {
            $this->warn("⚠️  Solo hay {$usuarios->count()} usuarios disponibles");
            $numeroUsuarios = $usuarios->count();
        }
        
        $this->line("   👥 Creando {$numeroUsuarios} pedidos...");
        
        // Crear pedidos en secuencia rápida (simula concurrencia)
        foreach ($usuarios as $index => $usuario) {
            try {
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
                
                $resultados[] = [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'asesor_id' => $pedido->asesor_id,
                    'estado' => $pedido->estado,
                    'usuario_index' => $index,
                    'ronda' => $ronda
                ];
                
                $this->line("     ✅ Pedido #{$pedido->id} - Usuario {$index}");
                
            } catch (\Exception $e) {
                $errores++;
                $this->error("     ❌ Error usuario {$index}: " . $e->getMessage());
                Log::error('[CONCURRENCIA_COMMAND] Error', [
                    'ronda' => $ronda,
                    'usuario_index' => $index,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $endTime = microtime(true);
        $duracion = round($endTime - $startTime, 3);
        
        // Verificar integridad de esta ronda
        $this->verificarIntegridad($resultados, $ronda);
        
        return [
            'pedidos' => count($resultados),
            'errores' => $errores,
            'duracion' => $duracion,
            'resultados' => $resultados
        ];
    }
    
    private function verificarIntegridad(array $pedidos, int $ronda): void
    {
        if (empty($pedidos)) {
            $this->warn("   ⚠️  Sin pedidos para verificar");
            return;
        }
        
        // Verificar IDs únicos
        $ids = array_column($pedidos, 'id');
        $idsUnicos = array_unique($ids);
        
        if (count($ids) !== count($idsUnicos)) {
            $this->error("   🚨 ¡IDS DUPLICADOS DETECTADOS!");
            Log::error('[CONCURRENCIA_COMMAND] IDs duplicados', [
                'ronda' => $ronda,
                'ids' => $ids,
                'duplicados' => array_diff_assoc($ids, $idsUnicos)
            ]);
        } else {
            $this->info("   ✅ IDs únicos verificadas");
        }
        
        // Verificar que numero_pedido sea null
        $numerosNoNulos = array_filter(array_column($pedidos, 'numero_pedido'));
        if (!empty($numerosNoNulos)) {
            $this->error("   🚨 ¡NÚMEROS DE PEDIDO NO DEBEN SER NULOS!");
        } else {
            $this->info("   ✅ números de pedido correctos (null)");
        }
        
        // Verificar secuencia de IDs
        sort($ids);
        $esperado = range($ids[0], $ids[0] + count($ids) - 1);
        if ($ids !== $esperado) {
            $this->error("   🚨 ¡SECUENCIA DE IDS ROTA!");
            Log::error('[CONCURRENCIA_COMMAND] Secuencia rota', [
                'ronda' => $ronda,
                'esperado' => $esperado,
                'recibido' => $ids
            ]);
        } else {
            $this->info("   ✅ Secuencia de IDs correcta");
        }
        
        $this->line("   📊 Rango IDs: " . min($ids) . " - " . max($ids));
    }
    
    private function mostrarResultadosFinales(int $totalPedidos, int $totalErrores, float $duracionTotal): void
    {
        $this->line("\n📊 RESULTADOS FINALES");
        $this->line("==================");
        
        $this->line("Pedidos creados: {$totalPedidos}");
        $this->line("Errores: {$totalErrores}");
        $this->line("Duración total: " . round($duracionTotal, 3) . "s");
        $this->line("Promedio por pedido: " . round($duracionTotal / max($totalPedidos, 1), 3) . "s");
        
        if ($totalErrores === 0) {
            $this->info("\n✅ PRUEBA EXITOSA - Sin errores de concurrencia");
        } else {
            $this->error("\n❌ PRUEBA CON ERRORES - Revisar logs");
        }
        
        // Verificación final en BD
        $this->verificacionFinalBD();
    }
    
    private function verificacionFinalBD(): void
    {
        $this->line("\n🔍 VERIFICACIÓN FINAL EN BD");
        $this->line("==========================");
        
        // Contar pedidos creados
        $totalPedidosBD = DB::table('pedidos_produccion')->count();
        $this->line("Total pedidos en BD: {$totalPedidosBD}");
        
        // Verificar AUTO_INCREMENT
        $autoIncrement = DB::select("
            SELECT AUTO_INCREMENT 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos_produccion'
        ")[0]->AUTO_INCREMENT ?? 'unknown';
        
        $this->line("Próximo AUTO_INCREMENT: {$autoIncrement}");
        
        // Verificar secuencia de números de pedido
        $secuencia = DB::table('numero_secuencias')
            ->where('tipo', 'pedido_produccion')
            ->first();
            
        if ($secuencia) {
            $this->line("Siguiente número pedido: {$secuencia->siguiente}");
            $this->line("Último usado: " . ($secuencia->ultimo_usado ?? 'N/A'));
        }
        
        // Buscar anomalías
        $idsDuplicados = DB::table('pedidos_produccion')
            ->select('id', DB::raw('COUNT(*) as count'))
            ->groupBy('id')
            ->having('count', '>', 1)
            ->get();
            
        if ($idsDuplicados->isNotEmpty()) {
            $this->error("🚨 IDS DUPLICADOS EN BD:");
            foreach ($idsDuplicados as $dup) {
                $this->error("  ID {$dup->id}: {$dup->count} veces");
            }
        } else {
            $this->info("✅ Sin IDs duplicados en BD");
        }
    }
}
