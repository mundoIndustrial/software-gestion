<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckImagenesPedidoTables extends Command
{
    protected $signature = 'check:imagenes-pedido-tables';
    protected $description = 'Verificar tablas de imágenes de pedidos';

    public function handle()
    {
        $this->info("═══════════════════════════════════════════════════════════════");
        $this->info("VERIFICANDO TABLAS DE IMÁGENES EN PEDIDOS");
        $this->info("═══════════════════════════════════════════════════════════════\n");

        $tablas = [
            'prenda_fotos_pedido',
            'prenda_fotos_tela_pedido',
            'prenda_fotos_logo_pedido'
        ];

        foreach ($tablas as $tabla) {
            $existe = DB::getSchemaBuilder()->hasTable($tabla);
            if ($existe) {
                $this->line("✅ $tabla");
                
                $count = DB::table($tabla)->count();
                $this->line("   Registros: $count");
                
                // Mostrar primero
                $primero = DB::table($tabla)->first();
                if ($primero) {
                    $this->line("   Primer registro:");
                    foreach ((array)$primero as $col => $val) {
                        $this->line("     - $col: " . substr($val, 0, 50));
                    }
                }
            } else {
                $this->error("❌ $tabla NO EXISTE");
            }
            $this->line("");
        }
    }
}
