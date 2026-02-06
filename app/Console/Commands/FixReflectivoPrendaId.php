<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;

class FixReflectivoPrendaId extends Command
{
    protected $signature = 'fix:reflectivo-prenda-id';
    protected $description = 'Fix REFLECTIVO recibos without prenda_id by assigning the first prenda from the pedido';

    public function handle()
    {
        $this->info('=== FIX REFLECTIVO RECIBOS ===');
        
        // Obtener todos los recibos REFLECTIVO sin prenda_id
        $recibosReflectivo = ConsecutivoReciboPedido::where('tipo_recibo', 'REFLECTIVO')
            ->whereNull('prenda_id')
            ->get();
        
        $this->info("Recibos REFLECTIVO sin prenda_id encontrados: " . $recibosReflectivo->count());
        
        foreach ($recibosReflectivo as $recibo) {
            // Obtener el pedido
            $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
            
            if (!$pedido) {
                $this->warn("  ✗ Recibo ID {$recibo->id}: Pedido no encontrado");
                continue;
            }
            
            // Obtener la primera prenda de bodega (de_bodega = true) o la primera prenda
            $prenda = $pedido->prendas()
                ->where('de_bodega', 1)
                ->first();
            
            if (!$prenda) {
                // Si no hay prenda de bodega, tomar la primera prenda
                $prenda = $pedido->prendas()->first();
            }
            
            if (!$prenda) {
                $this->warn("  ✗ Recibo ID {$recibo->id}: No se encontró prenda para el pedido");
                continue;
            }
            
            // Actualizar el recibo
            $recibo->update(['prenda_id' => $prenda->id]);
            $this->line("  ✓ Recibo ID {$recibo->id}: Asignado prenda_id = {$prenda->id} ({$prenda->nombre_prenda})");
        }
        
        $this->info("\n✅ Proceso completado");
    }
}
