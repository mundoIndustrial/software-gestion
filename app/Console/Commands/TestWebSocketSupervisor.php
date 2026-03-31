<?php

namespace App\Console\Commands;

use App\Events\OrdenUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestWebSocketSupervisor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-websocket-supervisor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simular evento OrdenUpdated para probar WebSocket en supervisor-pedidos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info(' Iniciando prueba de WebSocket para supervisor-pedidos...');
        
        // Simular datos de una orden
        $ordenData = [
            'id' => 99999,
            'numero_pedido' => 'WS-TEST-' . time(),
            'cliente' => 'Cliente WebSocket Test',
            'estado' => 'Aprobado',
            'novedades' => 'Probando WebSocket desde supervisor-pedidos',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        // Crear objeto simulado
        $orden = (object) $ordenData;
        
        $this->info(" Pedido: {$orden->numero_pedido}");
        $this->info(" Acción: created");
        $this->info(" Canales: supervisor-pedidos, ordenes");
        
        Log::info('[TEST] Simulando evento OrdenUpdated', [
            'numero_pedido' => $orden->numero_pedido,
            'estado' => $orden->estado
        ]);
        
        try {
            // Disparar evento
            event(new OrdenUpdated($orden, 'created', ['numero_pedido', 'estado']));
            
            Log::info('[TEST] Evento OrdenUpdated disparado correctamente', [
                'numero_pedido' => $orden->numero_pedido
            ]);
            
            $this->info(' Evento OrdenUpdated disparado correctamente');
            $this->info(' Revisa la consola del navegador en supervisor-pedidos');
            $this->info('🌐 Abre /websocket-test-supervisor.html para verificar recepción');
            
        } catch (\Exception $e) {
            Log::error('[TEST] Error al disparar evento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->error(" Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
