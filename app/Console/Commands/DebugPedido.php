<?php

namespace App\Console\Commands;

use App\Models\PedidoProduccion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugPedido extends Command
{
    protected $signature = 'pedido:debug {id : ID del pedido a inspeccionar}';
    protected $description = 'Inspecciona un pedido específico en detalle';

    public function handle()
    {
        $id = $this->argument('id');

        $this->info("🔍 Inspeccionando pedido: {$id}\n");

        // 1. Query directa a la BD
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('1️⃣  QUERY DIRECTA A BD:');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $raw = DB::table('pedidos_produccion')->where('id', $id)->first();
        if ($raw) {
            $this->info(" Encontrado en BD");
            $this->table(['Campo', 'Valor'], array_map(fn($k, $v) => [$k, $v], array_keys((array)$raw), array_values((array)$raw)));
        } else {
            $this->error(" NO encontrado en BD");
        }

        // 2. Con SoftDeletes incluidos
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('2️⃣  CON SOFT DELETES INCLUIDOS:');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $withTrashed = PedidoProduccion::withTrashed()->find($id);
        if ($withTrashed) {
            $this->info(" Encontrado con withTrashed()");
            $this->info("  - ID: {$withTrashed->id}");
            $this->info("  - Número: {$withTrashed->numero_pedido}");
            $this->info("  - Cliente: {$withTrashed->cliente}");
            $this->info("  - deleted_at: " . ($withTrashed->deleted_at ? $withTrashed->deleted_at : "NULL"));
            $this->info("  - trashed(): " . ($withTrashed->trashed() ? "SÍ" : "NO"));
        } else {
            $this->error(" NO encontrado ni con withTrashed()");
        }

        // 3. Sin soft deletes (query normal)
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('3️⃣  SIN SOFT DELETES (NORMAL):');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $normal = PedidoProduccion::find($id);
        if ($normal) {
            $this->info(" Encontrado con find() normal");
            $this->info("  - ID: {$normal->id}");
            $this->info("  - Número: {$normal->numero_pedido}");
            $this->info("  - Cliente: {$normal->cliente}");
        } else {
            $this->error(" NO encontrado con find() normal");
        }

        // 4. SQL Query
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('4️⃣  QUERIES SQL EJECUTADAS:');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Habilitar query logging
        DB::enableQueryLog();
        
        DB::table('pedidos_produccion')->where('id', $id)->first();
        PedidoProduccion::withTrashed()->find($id);
        PedidoProduccion::find($id);
        
        $queries = DB::getQueryLog();
        foreach ($queries as $i => $query) {
            $sql = substr($query['query'], 0, 100);
            $this->line("Query " . ($i + 1) . ": " . $sql);
        }

        // 5. Test de eliminación
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('5️⃣  TEST DE ELIMINACIÓN:');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $testPedido = PedidoProduccion::withTrashed()->find($id);
        if ($testPedido) {
            if ($testPedido->trashed()) {
                $this->warn("⚠️  El pedido ya está eliminado (soft deleted)");
            } else {
                $this->info(" El pedido está activo, puede eliminarse");
            }
        }
    }
}
