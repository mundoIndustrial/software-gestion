<?php

/**
 * Comando Artisan para probar generación de consecutivos
 * Ejecutar: php artisan test:consecutivos
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PedidoProduccion;
use App\Services\ConsecutivosRecibosService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestConsecutivosCommand extends Command
{
    protected $signature = 'test:consecutivos';
    protected $description = 'Probar generación de consecutivos para pedidos';

    public function handle()
    {
        $this->info(' Probando generación de consecutivos...');

        try {
            // 1. Buscar un pedido en estado PENDIENTE_SUPERVISOR
            $pedido = PedidoProduccion::where('estado', 'PENDIENTE_SUPERVISOR')
                ->first();

            if (!$pedido) {
                $this->error(' No hay pedidos en estado PENDIENTE_SUPERVISOR');
                $this->info(' Estados disponibles:');
                
                $estados = PedidoProduccion::distinct()->pluck('estado');
                foreach ($estados as $estado) {
                    $this->info("   - $estado");
                }
                return 1;
            }

            $this->info(' Pedido encontrado:');
            $this->info("   ID: {$pedido->id}");
            $this->info("   Número: " . ($pedido->numero_pedido ?? 'SIN NÚMERO'));
            $this->info("   Cliente: {$pedido->cliente}");
            $this->info("   Estado actual: {$pedido->estado}");

            // 2. Verificar si ya tiene consecutivos
            $consecutivosExistentes = DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedido->id)
                ->count();

            if ($consecutivosExistentes > 0) {
                $this->warn("  El pedido ya tiene {$consecutivosExistentes} consecutivos generados");
                $this->info(' Mostrando consecutivos existentes:');
                
                $existentes = DB::table('consecutivos_recibos_pedidos')
                    ->where('pedido_produccion_id', $pedido->id)
                    ->get();
                    
                foreach ($existentes as $cons) {
                    $this->info("   - {$cons->tipo_recibo}: {$cons->consecutivo_actual}");
                }
            }

            // 3. Simular el cambio de estado
            $this->info(' Simulando cambio de estado: PENDIENTE_SUPERVISOR → PENDIENTE_INSUMOS');
            
            $estadoAnterior = $pedido->estado;
            $estadoNuevo = 'PENDIENTE_INSUMOS';
            
            // 4. Probar el servicio directamente
            $service = new ConsecutivosRecibosService();
            $resultado = $service->generarConsecutivosSiAplica($pedido, $estadoAnterior, $estadoNuevo);
            
            if ($resultado) {
                $this->info(' Consecutivos generados exitosamente');
                
                // Mostrar los consecutivos generados
                $nuevosConsecutivos = DB::table('consecutivos_recibos_pedidos')
                    ->where('pedido_produccion_id', $pedido->id)
                    ->get();
                    
                $this->info(' Consecutivos del pedido:');
                foreach ($nuevosConsecutivos as $cons) {
                    $this->info("   - {$cons->tipo_recibo}: {$cons->consecutivo_actual} (inicial: {$cons->consecutivo_inicial})");
                    $this->info("     Notas: {$cons->notas}");
                }
                
                // Preguntar si desea actualizar el estado realmente
                if ($this->confirm('¿Desea actualizar el estado del pedido a PENDIENTE_INSUMOS?')) {
                    $pedido->update(['estado' => $estadoNuevo]);
                    $this->info(" Estado del pedido actualizado a: {$estadoNuevo}");
                }
                
            } else {
                $this->info('  No se generaron consecutivos (revisar logs para más detalles)');
            }

        } catch (\Exception $e) {
            $this->error(" Error: " . $e->getMessage());
            $this->error(" Línea: " . $e->getLine());
            $this->error(" Archivo: " . $e->getFile());
            return 1;
        }

        $this->info('🏁 Fin de la prueba');
        return 0;
    }
}
