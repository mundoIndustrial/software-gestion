<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PedidoProduccion;

class DebugPedido2596 extends Command
{
    protected $signature = 'debug:pedido-2596';
    protected $description = 'Verificar datos del pedido 2596';

    public function handle()
    {
        $pedido = PedidoProduccion::find(2596);
        if ($pedido) {
            $this->info("✅ Pedido 2596 encontrado");
            $this->info("Prendas en pedido: " . $pedido->prendas->count());
            
            foreach ($pedido->prendas as $prenda) {
                $this->line("\n  📌 Prenda: " . $prenda->nombre_prenda);
                $this->line("  - cantidad_talla: " . json_encode($prenda->cantidad_talla));
                $this->line("  - fotos: " . $prenda->fotos->count());
                $this->line("  - fotosTelas: " . $prenda->fotosTelas->count());
            }
            
            $this->info("\n\nEPPs del pedido: " . $pedido->epps->count());
            if ($pedido->epps->count() > 0) {
                foreach ($pedido->epps as $epp) {
                    $this->line("  - EPP: " . ($epp->epp?->nombre ?? 'Desconocido') . ", Cantidad: " . $epp->cantidad);
                }
            }
        } else {
            $this->error("❌ Pedido 2596 no encontrado");
        }
    }
}
