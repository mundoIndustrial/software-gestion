<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Application\Pedidos\UseCases\ObtenerProcesosPorPedidoUseCase;
use App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController;
use App\Http\Controllers\Api_temp\PedidoController;

class AnalizarTrackingDatos extends Command
{
    protected $signature = 'analizar:tracking-datos {pedidoId=45808}';
    protected $description = 'Analizar qué datos se envían en el tracking';

    public function handle()
    {
        $pedidoId = $this->argument('pedidoId');
        
        $this->info("\n╔══════════════════════════════════════════════════════════════════╗");
        $this->info("║       ANÁLISIS: DATOS EN BD vs DATOS EN RESPUESTA               ║");
        $this->info("╚══════════════════════════════════════════════════════════════════╝\n");

        // 1. DATOS EN BD
        $this->line("1️⃣  DATOS DIRECTOS EN BASE DE DATOS");
        $this->line("─────────────────────────────────────────────────────────────────");

        $pedidoDb = \DB::table('pedidos_produccion')
            ->where('numero_pedido', $pedidoId)
            ->orWhere('id', $pedidoId)
            ->first();

        if ($pedidoDb) {
            $this->info("✓ Pedido encontrado (ID: {$pedidoDb->id}, numero_pedido: {$pedidoDb->numero_pedido})");
            $this->newLine();
            $this->line("Campos principales:");
            $this->line("  • estado: " . ($pedidoDb->estado ?? 'NULL'));
            $this->line("  • fecha_de_creacion_de_orden: " . ($pedidoDb->fecha_de_creacion_de_orden ?? 'NULL'));
            $this->line("  • fecha_estimada_de_entrega: " . ($pedidoDb->fecha_estimada_de_entrega ?? 'NULL'));
            $this->line("  • cliente: " . ($pedidoDb->cliente ?? 'NULL'));
            $this->line("  • area: " . ($pedidoDb->area ?? 'NULL'));
            $this->line("  • created_at: " . ($pedidoDb->created_at ?? 'NULL'));
        } else {
            $this->error("✗ Pedido NO encontrado en BD");
            return;
        }

        $this->newLine(2);

        // 2. PROCESOS DEL PEDIDO
        $this->line("2️⃣  PROCESOS DEL PEDIDO EN BD");
        $this->line("─────────────────────────────────────────────────────────────────");

        $procesos = \DB::table('procesos_prenda')
            ->where('numero_pedido', $pedidoId)
            ->get();

        $this->info("✓ Procesos encontrados: " . count($procesos));
        foreach ($procesos as $proceso) {
            $this->line("  • Proceso: {$proceso->proceso} | Estado: {$proceso->estado_proceso}");
        }

        $this->newLine(2);

        // 3. USECASE
        $this->line("3️⃣  USECASE ObtenerProcesosPorPedidoUseCase");
        $this->line("─────────────────────────────────────────────────────────────────");

        try {
            $useCase = app(ObtenerProcesosPorPedidoUseCase::class);
            $resultado = $useCase->ejecutar($pedidoId);
            
            $this->info("✓ UseCase ejecutado exitosamente\n");
            
            $resultArray = $resultado;
            $this->line("Campos devueltos:");
            $this->line("  • numero_pedido: " . ($resultado->numero_pedido ?? 'NULL'));
            $this->line("  • cliente: " . ($resultado->cliente ?? 'NULL'));
            $this->line("  • estado: " . ($resultado->estado ?? 'NULL'));
            $this->line("  • fecha_de_creacion_de_orden: " . ($resultado->fecha_de_creacion_de_orden ?? 'NULL'));
            $this->line("  • procesos (count): " . count($resultado->procesos ?? []));
            
            if (!empty($resultado->procesos)) {
                $this->line("\n  Procesos en UseCase:");
                foreach ($resultado->procesos as $p) {
                    $this->line("    - " . $p['proceso'] . " (" . $p['estado_proceso'] . ")");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("✗ Error en UseCase: " . $e->getMessage());
        }

        $this->newLine(2);

        // 4. API ENDPOINT
        $this->line("4️⃣  API ENDPOINT getProcesos");
        $this->line("─────────────────────────────────────────────────────────────────");

        try {
            $controller = app(PedidosProduccionController::class);
            $response = $controller->getProcesos($pedidoId);
            $apiData = json_decode($response->getContent(), true);
            
            $this->info("✓ API ejecutada\n");
            $this->line("Procesos en respuesta: " . count($apiData));
            
            foreach ($apiData as $p) {
                $this->line("  • " . $p['proceso'] . " | Estado: " . $p['estado_proceso']);
            }
            
        } catch (\Exception $e) {
            $this->error("✗ Error en API: " . $e->getMessage());
        }

        $this->newLine(2);

        // 5. obtenerDetalleCompleto
        $this->line("5️⃣  obtenerDetalleCompleto (para recibos-datos)");
        $this->line("─────────────────────────────────────────────────────────────────");

        try {
            $pedidoController = app(PedidoController::class);
            $response = $pedidoController->obtenerDetalleCompleto($pedidoId, false);
            $detalleData = json_decode($response->getContent(), true);
            
            $this->info("✓ Ejecutado\n");
            
            if (isset($detalleData['data'])) {
                $data = $detalleData['data'];
                $this->line("Campos principales:");
                $this->line("  • numero: " . ($data['numero'] ?? 'NULL'));
                $this->line("  • numero_pedido: " . ($data['numero_pedido'] ?? 'NULL'));
                $this->line("  • cliente: " . ($data['cliente'] ?? 'NULL'));
                $this->line("  • estado: " . ($data['estado'] ?? 'NULL'));
                $this->line("  • fecha_creacion: " . ($data['fecha_creacion'] ?? 'NULL'));
                $this->line("  • fecha: " . ($data['fecha'] ?? 'NULL'));
                $this->line("  • area: " . ($data['area'] ?? 'NULL'));
            }
            
        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
        }

        $this->newLine(2);

        // 6. CONCLUSIONES
        $this->line("6️⃣  CONCLUSIONES");
        $this->line("─────────────────────────────────────────────────────────────────");
        
        $this->info("✓ Análisis completado. Verifica los datos anteriores.\n");
    }
}
