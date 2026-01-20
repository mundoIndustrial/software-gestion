<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CrearSecuenciaPedidos extends Command
{
    protected $signature = 'crear:secuencia-pedidos';
    protected $description = 'Crear secuencia universal para pedidos de producción';

    public function handle()
    {
        $this->info('Verificando secuencia pedidos_produccion_universal...');

        try {
            $seq = DB::table('numero_secuencias')
                ->where('tipo', 'pedidos_produccion_universal')
                ->first();
            
            if ($seq) {
                $this->info(" Secuencia YA EXISTE");
                $this->line("   Tipo: {$seq->tipo}");
                $this->line("   Siguiente: {$seq->siguiente}");
            } else {
                $this->warn("⚠️  Secuencia NO EXISTE, creando...");
                
                // Obtener el máximo actual
                $maxActual = DB::table('pedidos_produccion')->max('numero_pedido');
                $siguienteNumero = ($maxActual ? $maxActual + 1 : 1);
                
                $this->line("   Max actual en BD: $maxActual");
                $this->line("   Siguiente a usar: $siguienteNumero");
                
                DB::table('numero_secuencias')->insert([
                    'tipo' => 'pedidos_produccion_universal',
                    'siguiente' => $siguienteNumero,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $this->info(" Secuencia CREADA");
            }
            
            // Mostrar todas
            $this->line("\n Todas las secuencias en numero_secuencias:");
            $todas = DB::table('numero_secuencias')->get();
            foreach ($todas as $s) {
                $this->line("   - {$s->tipo}: {$s->siguiente}");
            }
            
        } catch (\Exception $e) {
            $this->error(" Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
