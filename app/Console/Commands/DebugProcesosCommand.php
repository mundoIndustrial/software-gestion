<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PrendaPedido;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

class DebugProcesosCommand extends Command
{
    protected $signature = 'debug:procesos {--pedido=8}';
    protected $description = 'Diagnostica el problema con procesos_prenda';

    public function handle()
    {
        $numeroPedido = $this->option('pedido');
        
        $this->info("\n========== DIAGNOSTICO DE PROCESOS PRENDA ==========\n");

        // 0. Obtener PedidoProduccion primero
        $this->line("0️⃣ Buscando PedidoProduccion #{$numeroPedido}:");
        $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
        
        if (!$pedidoProduccion) {
            $this->error("    Pedido #{$numeroPedido} no encontrado");
            return;
        }
        
        $this->line("    Encontrado - ID: {$pedidoProduccion->id}");

        // 1. Verificar prendas del pedido
        $this->line("\n1️⃣ Prendas del Pedido #{$numeroPedido}:");
        $prendas = PrendaPedido::where('pedido_produccion_id', $pedidoProduccion->id)->get();
        $this->line("   Total prendas: " . $prendas->count());

        foreach ($prendas as $prenda) {
            $this->line("   - ID: {$prenda->id}, Nombre: {$prenda->nombre_prenda}");
            $this->line("     pedido_produccion_id: {$prenda->pedido_produccion_id}");
        }

        // 2. Procesos en BD directamente
        $this->line("\n2️⃣ Procesos en tabla procesos_prenda (numero_pedido = {$numeroPedido}):");
        $procesosBD = DB::table('procesos_prenda')
            ->where('numero_pedido', $numeroPedido)
            ->get();
        
        $this->line("   Total encontrados: " . $procesosBD->count());
        foreach ($procesosBD as $p) {
            $this->line("   - ID: {$p->id}");
            $this->line("     numero_pedido: {$p->numero_pedido}");
            $this->line("     prenda_pedido_id: {$p->prenda_pedido_id}");
            $this->line("     encargado: {$p->encargado}");
            $this->line("");
        }

        // 3. Relación Eloquent
        $this->line("3️⃣ Probando relación procesosPrenda() (hasManyThrough):");
        $prenda = $prendas->first();
        
        if ($prenda) {
            $this->line("   Prenda: {$prenda->nombre_prenda}");
            $this->line("   - ID: {$prenda->id}");
            $this->line("   - pedido_produccion_id: {$prenda->pedido_produccion_id}");
            
            $procesos = $prenda->procesosPrenda()->get();
            $this->line("   Procesos via relación: " . $procesos->count());
            
            foreach ($procesos as $p) {
                $this->line("   - {$p->encargado}");
            }

            // Query SQL
            $this->line("\n4️⃣ Query SQL de la relación:");
            $query = $prenda->procesosPrenda();
            $this->line("   SQL: " . $query->toSql());
            $this->line("   Bindings: " . json_encode($query->getBindings()));
        }

        // 5. Resumen
        $this->line("\n5️⃣ RESUMEN:");
        if ($procesosBD->count() === 0) {
            $this->error("    NO HAY PROCESOS en procesos_prenda para este pedido");
        } elseif ($prenda && $prenda->procesosPrenda()->count() === 0) {
            $this->error("    RELACION QUEBRADA: Hay datos pero la relación no los carga!");
        } else {
            $this->info("    RELACION OK: Los procesos se cargan correctamente");
        }

        $this->info("\n========== FIN DIAGNOSTICO ==========\n");
    }
}

