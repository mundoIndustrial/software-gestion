<?php

namespace App\Console\Commands;

use App\Models\LogoPedido;
use App\Models\ProcesosPedidosLogo;
use Illuminate\Console\Command;

class InitializeLogoPedidoProcesses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:initialize-logo-pedido-processes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inicializa los procesos iniciales para todos los pedidos de logo existentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Inicializando procesos de pedidos LOGO...');

        $logoPedidos = LogoPedido::all();
        $procesosCreados = 0;

        foreach ($logoPedidos as $logoPedido) {
            // Verificar si ya tiene un proceso
            $tieneProceso = ProcesosPedidosLogo::where('logo_pedido_id', $logoPedido->id)->exists();

            if (!$tieneProceso) {
                ProcesosPedidosLogo::crearProcesoInicial($logoPedido->id);
                $procesosCreados++;
                $this->line("✅ Proceso creado para: {$logoPedido->numero_pedido}");
            } else {
                $this->line("⏭️  Ya existe proceso para: {$logoPedido->numero_pedido}");
            }
        }

        $this->info("✨ Proceso completado. Se crearon {$procesosCreados} procesos iniciales.");
    }
}
