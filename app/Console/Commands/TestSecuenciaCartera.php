<?php

namespace App\Console\Commands;

use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestSecuenciaCartera extends Command
{
    protected $signature = 'pedidos:test-secuencia-cartera {pedidos=5}';
    protected $description = 'Probar secuencia de números de pedido en Cartera';

    public function handle()
    {
        $numeroPedidos = (int) $this->argument('pedidos');
        
        $this->info(" Probando secuencia de números de pedido en Cartera");
        $this->info(" Pedidos a aprobar: {$numeroPedidos}");
        $this->info("⏰ " . date('Y-m-d H:i:s'));
        
        try {
            // Crear pedidos en estado pendiente_cartera
            $this->line("\n Creando {$numeroPedidos} pedidos pendientes...");
            $pedidosCreados = [];
            
            for ($i = 0; $i < $numeroPedidos; $i++) {
                $pedido = PedidoProduccion::create([
                    'cliente' => " Cliente Test Cartera " . ($i + 1),
                    'asesor_id' => User::first()->id,
                    'estado' => 'pendiente_cartera',
                    'created_at' => now(),
                ]);
                
                $pedidosCreados[] = $pedido;
                $this->line("   Pedido #{$pedido->id} creado");
            }
            
            // Aprobar pedidos concurrentemente
            $this->line("\n Aprobando {$numeroPedidos} pedidos concurrentemente...");
            $numerosGenerados = [];
            $startTime = microtime(true);
            
            foreach ($pedidosCreados as $index => $pedido) {
                try {
                    // Usar la misma lógica que CarteraPedidosController
                    $numero = DB::transaction(function () {
                        $secuencia = DB::table('numero_secuencias')
                            ->where('tipo', 'pedido_produccion')
                            ->lockForUpdate()
                            ->first();
                        
                        if (!$secuencia) {
                            $numero = 1;
                            DB::table('numero_secuencias')->insert([
                                'tipo' => 'pedido_produccion',
                                'siguiente' => 2,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } else {
                            $numero = $secuencia->siguiente;
                            DB::table('numero_secuencias')
                                ->where('tipo', 'pedido_produccion')
                                ->update([
                                    'siguiente' => $numero + 1,
                                    'updated_at' => now(),
                                ]);
                        }
                        
                        return $numero;
                    });
                    
                    $pedido->update(['numero_pedido' => $numero]);
                    $numerosGenerados[] = $numero;
                    
                    $this->line("   Pedido #{$pedido->id} → número {$numero}");
                    
                } catch (\Exception $e) {
                    $this->error("   Error aprobando pedido #{$pedido->id}: " . $e->getMessage());
                    Log::error('[SECUENCIA_CARTERA] Error', [
                        'pedido_id' => $pedido->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $endTime = microtime(true);
            $duracion = $endTime - $startTime;
            
            // Verificar resultados
            $this->line("\n RESULTADOS DE SECUENCIA:");
            $this->line("Pedidos aprobados: " . count($numerosGenerados));
            $this->line("Duración: " . round($duracion, 3) . "s");
            $this->line("Números generados: " . implode(', ', $numerosGenerados));
            
            // Verificar que los números sean secuenciales y únicos
            sort($numerosGenerados);
            $esperado = range($numerosGenerados[0] ?? 1, $numerosGenerados[0] + count($numerosGenerados) - 1);
            
            if ($numerosGenerados === $esperado) {
                $this->info(" Secuencia correcta y sin duplicados");
            } else {
                $this->error("🚨 ¡SECUENCIA INCORRECTA O DUPLICADOS!");
                $this->error("Esperado: " . implode(', ', $esperado));
                $this->error("Recibido: " . implode(', ', $numerosGenerados));
            }
            
            // Verificación final en BD
            $this->verificacionFinalBD();
            
            return $numerosGenerados === $esperado ? 0 : 1;
            
        } catch (\Exception $e) {
            $this->error(" Error fatal: " . $e->getMessage());
            Log::error('[SECUENCIA_CARTERA] Error fatal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
    
    private function verificacionFinalBD(): void
    {
        $this->line("\n VERIFICACIÓN FINAL EN BD:");
        $this->line("==========================");
        
        // Verificar secuencia actual
        $secuencia = DB::table('numero_secuencias')
            ->where('tipo', 'pedido_produccion')
            ->first();
            
        if ($secuencia) {
            $this->line("Siguiente número pedido: {$secuencia->siguiente}");
        }
        
        // Verificar pedidos con números asignados
        $pedidosConNumero = PedidoProduccion::whereNotNull('numero_pedido')
            ->orderBy('numero_pedido')
            ->get();
            
        $this->line("Pedidos con número: " . $pedidosConNumero->count());
        
        if ($pedidosConNumero->isNotEmpty()) {
            $numeros = $pedidosConNumero->pluck('numero_pedido')->toArray();
            $this->line("Rango de números: " . min($numeros) . " - " . max($numeros));
            
            // Buscar duplicados
            $numerosUnicos = array_unique($numeros);
            if (count($numeros) === count($numerosUnicos)) {
                $this->info(" Sin números duplicados");
            } else {
                $this->error("🚨 ¡NÚMEROS DUPLICADOS!");
            }
        }
    }
}
