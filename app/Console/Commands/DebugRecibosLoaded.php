<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConsecutivoReciboPedido;

class DebugRecibosLoadedForReflectivo extends Command
{
    protected $signature = 'debug:recibos-loaded';
    protected $description = 'Debug what recibos are loaded for costura-reflectivo';

    public function handle()
    {
        $this->info('=== RECIBOS CARGADOS PARA COSTURA-REFLECTIVO ===');
        
        // Simulamos que el servicio busca COSTURA y REFLECTIVO
        $tiposRecibo = ['COSTURA', 'REFLECTIVO'];
        
        $recibos = ConsecutivoReciboPedido::where('activo', 1)
            ->whereIn('tipo_recibo', $tiposRecibo)
            ->with(['prenda', 'prenda.pedidoProduccion'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $this->info("Total recibos con COSTURA + REFLECTIVO: " . $recibos->count());
        $this->newLine();
        
        foreach ($recibos as $recibo) {
            $this->line("Recibo ID: {$recibo->id}");
            $this->line("  Tipo: {$recibo->tipo_recibo}");
            $this->line("  Prenda ID: {$recibo->prenda_id}");
            $this->line("  Pedido ID: {$recibo->pedido_produccion_id}");
            if ($recibo->prenda) {
                $this->line("  Prenda Nombre: {$recibo->prenda->nombre_prenda}");
            }
            $this->newLine();
        }
        
        // Verificar COSTURA-BODEGA
        $this->info("=== RECIBOS COSTURA-BODEGA ===");
        $costuraBodega = ConsecutivoReciboPedido::where('activo', 1)
            ->where('tipo_recibo', 'COSTURA-BODEGA')
            ->get();
        
        $this->info("Total COSTURA-BODEGA: " . $costuraBodega->count());
        foreach ($costuraBodega as $cb) {
            $this->line("  ID {$cb->id}: Pedido {$cb->pedido_produccion_id}, Prenda {$cb->prenda_id}");
        }
    }
}
