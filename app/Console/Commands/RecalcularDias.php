<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PedidoProduccion;
use App\Models\Festivo;
use App\Services\CacheCalculosService;
use Illuminate\Support\Facades\Cache;

class RecalcularDias extends Command
{
    protected $signature = 'dias:recalcular {--limit=100}';
    protected $description = 'Recalcular y precachear los días para todas las órdenes';

    public function handle()
    {
        $this->info('🔄 Limpiando caché...');
        Cache::flush();
        
        $limit = $this->option('limit') ?? 100;
        $this->info("📊 Recalculando días para últimas {$limit} órdenes...\n");

        $ordenes = PedidoProduccion::orderBy('numero_pedido', 'DESC')->limit($limit)->get();
        
        $calculadas = 0;
        foreach ($ordenes as $orden) {
            try {
                $dias = CacheCalculosService::getTotalDias($orden->numero_pedido, $orden->estado);
                
                if ($dias > 0 || $orden->estado === 'No iniciado') {
                    $this->line("✅ Pedido {$orden->numero_pedido}: {$dias} días (Estado: {$orden->estado})");
                } else {
                    $this->warn("⚠️  Pedido {$orden->numero_pedido}: {$dias} días (Estado: {$orden->estado})");
                }
                
                $calculadas++;
            } catch (\Exception $e) {
                $this->error("❌ Error en pedido {$orden->numero_pedido}: " . $e->getMessage());
            }
        }
        
        $this->info("\n✅ Precálculo completado: {$calculadas} órdenes procesadas");
        $this->info("Los días se han recalculado y están en caché para acceso rápido");
    }
}
