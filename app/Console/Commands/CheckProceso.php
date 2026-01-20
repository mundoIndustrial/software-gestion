<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProcesoPrenda;

class CheckProceso extends Command
{
    protected $signature = 'check:proceso {pedido}';
    protected $description = 'Check proceso for a given pedido';

    public function handle()
    {
        $numeroPedido = $this->argument('pedido');
        
        $this->info("\n=== VERIFICANDO PEDIDO $numeroPedido ===\n");

        $procesos = ProcesoPrenda::where('numero_pedido', $numeroPedido)->get();

        if ($procesos->count() > 0) {
            $this->info(" PROCESOS ENCONTRADOS:\n");
            foreach ($procesos as $p) {
                $this->line("   ID: " . $p->id);
                $this->line("   Proceso: " . $p->proceso);
                $this->line("   Estado: " . $p->estado_proceso);
                $this->line("   Inicio: " . $p->fecha_inicio);
                $this->line("   Fin: " . ($p->fecha_fin ?? 'NULL'));
                $this->line("");
            }
        } else {
            $this->error(" NO hay procesos\n");
        }

        // Verificar bodega
        $bodega = \DB::table('tabla_original_bodega')->where('pedido', $numeroPedido)->first();
        if ($bodega) {
            $this->info("📦 EN BODEGA:");
            $this->line("   Estado: " . $bodega->estado);
        }
    }
}
