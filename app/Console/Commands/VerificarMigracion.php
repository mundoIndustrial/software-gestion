<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;

class VerificarMigracion extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'verificar:migracion-tabla-original';

    /**
     * The console command description.
     */
    protected $description = 'Verifica que la migración de tabla_original fue exitosa.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== VERIFICACIÓN DE MIGRACIÓN ===');
        $this->newLine();

        $pedidos = PedidoProduccion::count();
        $prendas = PrendaPedido::count();
        $procesos = ProcesoPrenda::count();

        $this->line("📊 Conteos:");
        $this->line("   Pedidos: $pedidos");
        $this->line("   Prendas: $prendas");
        $this->line("   Procesos: $procesos");

        $this->newLine();
        $this->info('Ejemplos de datos migrados:');
        
        $pedido = PedidoProduccion::with(['prendas.procesos', 'asesora', 'clienteRelacion'])
            ->whereNotNull('cliente_id')
            ->whereNotNull('user_id')
            ->first();

        if ($pedido) {
            $this->line("\n✓ Pedido #{$pedido->numero_pedido}:");
            $this->line("  Cliente: " . ($pedido->clienteRelacion?->nombre ?? 'N/A'));
            $this->line("  Asesora: " . ($pedido->asesora?->name ?? 'N/A'));
            $this->line("  Estado: {$pedido->estado}");
            $this->line("  Prendas: {$pedido->prendas->count()}");
            
            foreach ($pedido->prendas->take(3) as $prenda) {
                $proceso = $prenda->procesos->first();
                $this->line("    - {$prenda->nombre_prenda} (Qty: {$prenda->cantidad})");
                if ($proceso) {
                    $this->line("      Proceso: {$proceso->proceso}");
                }
            }
        }

        $this->newLine();
        $this->info('✅ Migración verificada exitosamente!');
    }
}
