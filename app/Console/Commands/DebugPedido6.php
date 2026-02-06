<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PedidoProduccion;
use App\Models\ConsecutivoReciboPedido;

class DebugPedido6 extends Command
{
    protected $signature = 'debug:pedido6';
    protected $description = 'Debug Pedido 6';

    public function handle()
    {
        $this->info('=== DEBUG PEDIDO #6 ===');
        
        $pedido = PedidoProduccion::find(6);
        if (!$pedido) {
            $this->error('Pedido no encontrado');
            return;
        }
        
        $this->line("Pedido ID: {$pedido->id}");
        $this->line("Numero: {$pedido->numero_pedido}");
        $this->line("Estado: {$pedido->estado}");
        $this->line("Área: {$pedido->area}");
        $this->newLine();
        
        $this->info("Prendas del pedido:");
        $prendas = $pedido->prendas()->get();
        foreach ($prendas as $prenda) {
            $this->line("  - Prenda ID: {$prenda->id}, Nombre: {$prenda->nombre_prenda}");
        }
        $this->newLine();
        
        $this->info("Recibos del pedido:");
        $recibos = ConsecutivoReciboPedido::where('pedido_produccion_id', 6)->get();
        foreach ($recibos as $recibo) {
            $this->line("  - Recibo ID: {$recibo->id}, Prenda ID: {$recibo->prenda_id}, Tipo: {$recibo->tipo_recibo}, Consecutivo: {$recibo->consecutivo_actual}");
        }
    }
}
