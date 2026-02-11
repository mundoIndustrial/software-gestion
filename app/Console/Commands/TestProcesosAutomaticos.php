<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Insumos\ProcesoAutomaticoService;
use App\Services\Insumos\MaterialesService;
use App\Repositories\Insumos\MaterialesRepository;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesosPrenda;

class TestProcesosAutomaticos extends Command
{
    protected $signature = 'test:procesos-automaticos {--limpiar : Eliminar datos de prueba al final}';
    protected $description = 'Probar la creación automática de procesos al aprobar pedidos';

    public function handle()
    {
        $this->info('=== PRUEBA DE CREACIÓN AUTOMÁTICA DE PROCESOS ===');
        $this->line('');

        // 1. Buscar un pedido en estado Pendiente para probar
        $this->info('1. Buscando pedido en estado "Pendiente"...');
        $pedidoPendiente = PedidoProduccion::where('estado', 'Pendiente')->first();

        if (!$pedidoPendiente) {
            $this->error(' No se encontraron pedidos en estado "Pendiente" para probar');
            $this->info('   Creando un pedido de prueba...');
            
            // Crear pedido de prueba si no existe
            $numeroPedido = time(); // Usar timestamp como número entero
            $pedidoPendiente = PedidoProduccion::create([
                'numero_pedido' => $numeroPedido,
                'cliente' => 'Cliente Prueba',
                'estado' => 'Pendiente',
                'area' => 'Creación de orden',
                'fecha_de_creacion_de_orden' => now(),
                'asesor' => 'Sistema',
                'forma_de_pago' => 'Contado'
            ]);
            
            $this->info(" Pedido de prueba creado: {$pedidoPendiente->numero_pedido}");
        } else {
            $this->info(" Pedido encontrado: {$pedidoPendiente->numero_pedido}");
        }

        // 2. Verificar si tiene prendas asociadas
        $this->line('');
        $this->info('2. Verificando prendas del pedido...');
        $prendas = PrendaPedido::where('pedido_produccion_id', $pedidoPendiente->id)->get();

        if ($prendas->isEmpty()) {
            $this->warn('  El pedido no tiene prendas asociadas. Creando prendas de prueba...');
            
            // Crear prendas de prueba
            $prenda1 = PrendaPedido::create([
                'pedido_produccion_id' => $pedidoPendiente->id,
                'nombre_prenda' => 'Camisa Prueba',
                'descripcion' => 'Camisa para probar procesos automáticos',
                'de_bodega' => false
            ]);
            
            $prenda2 = PrendaPedido::create([
                'pedido_produccion_id' => $pedidoPendiente->id,
                'nombre_prenda' => 'Polo Prueba',
                'descripcion' => 'Polo con bordado para probar procesos',
                'de_bodega' => true
            ]);
            
            $this->info(' Creadas 2 prendas de prueba');
            $prendas = PrendaPedido::where('pedido_produccion_id', $pedidoPendiente->id)->get();
        } else {
            $this->info(" El pedido tiene {$prendas->count()} prendas asociadas");
        }

        // 3. Probar la creación automática de procesos
        $this->line('');
        $this->info('3. Probando creación automática de procesos...');
        $procesoService = new ProcesoAutomaticoService();
        $resultado = $procesoService->crearProcesosParaPedido($pedidoPendiente->numero_pedido);

        if ($resultado['success']) {
            $this->info(' Procesos creados exitosamente');
            $this->line("   Total procesos creados: {$resultado['procesos_creados']}");
            
            if (!empty($resultado['detalles'])) {
                $this->line('   Detalles:');
                foreach ($resultado['detalles'] as $detalle) {
                    $this->line("     - {$detalle}");
                }
            }
        } else {
            $this->error(" Error al crear procesos: {$resultado['message']}");
        }

        // 4. Verificar los procesos creados en la BD
        $this->line('');
        $this->info('4. Verificando procesos en la base de datos...');
        $procesosCreados = ProcesosPrenda::where('numero_pedido', $pedidoPendiente->numero_pedido)->get();

        $this->line(" Total de procesos en BD: {$procesosCreados->count()}");
        foreach ($procesosCreados as $proceso) {
            $this->line("   - ID: {$proceso->id}, Proceso: {$proceso->proceso}, Estado: {$proceso->estado_proceso}");
            $this->line("     Fecha inicio: {$proceso->fecha_inicio}, Código: {$proceso->codigo_referencia}");
        }

        // 5. Probar el flujo completo del MaterialesService
        $this->line('');
        $this->info('5. Probando flujo completo del MaterialesService...');
        
        // Primero revertir el estado del pedido para probar el flujo completo
        $pedidoPendiente->update(['estado' => 'Pendiente']);
        
        $materialesService = new MaterialesService(new MaterialesRepository());
        $resultadoCompleto = $materialesService->cambiarEstadoPedido($pedidoPendiente->numero_pedido, 'En Ejecución');

        if ($resultadoCompleto['success']) {
            $this->info(' Flujo completo exitoso');
            $this->line("   Mensaje: {$resultadoCompleto['message']}");
            $this->line("   Estado: {$resultadoCompleto['estado']}, Área: {$resultadoCompleto['area']}");
            $this->line("   Procesos creados: {$resultadoCompleto['procesos_creados']}");
            
            // Verificar que el pedido cambió de estado
            $pedidoActualizado = PedidoProduccion::find($pedidoPendiente->id);
            $this->line("   Estado actual del pedido: {$pedidoActualizado->estado}");
            $this->line("   Área actual del pedido: {$pedidoActualizado->area}");
        } else {
            $this->error(" Error en flujo completo: {$resultadoCompleto['message']}");
        }

        // 6. Limpiar datos de prueba si se solicita
        if ($this->option('limpiar')) {
            $this->line('');
            $this->info('6. Limpiando datos de prueba...');
            
            // Eliminar procesos creados
            ProcesosPrenda::where('numero_pedido', $pedidoPendiente->numero_pedido)->delete();
            
            // Eliminar prendas creadas
            PrendaPedido::where('pedido_produccion_id', $pedidoPendiente->id)->delete();
            
            // Eliminar pedido creado
            $pedidoPendiente->delete();
            
            $this->info(' Datos de prueba eliminados');
        }

        $this->line('');
        $this->info('=== FIN DE LA PRUEBA ===');
        $this->info("Pedido utilizado: {$pedidoPendiente->numero_pedido}");
        $this->info('Revise la tabla "procesos_prenda" para verificar los resultados');
        
        return Command::SUCCESS;
    }
}
