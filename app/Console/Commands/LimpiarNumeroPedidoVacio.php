<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimpiarNumeroPedidoVacio extends Command
{
    protected $signature = 'fix:limpiar-numero-pedido-vacio';
    protected $description = 'Limpiar registros con numero_pedido vacío antes de convertir a INT';

    public function handle()
    {
        $this->info("\n" . str_repeat("=", 140));
        $this->info("🧹 LIMPIEZA: Eliminar registros con numero_pedido vacío");
        $this->info(str_repeat("=", 140) . "\n");

        try {
            // Contar registros vacíos
            $countVacios = DB::table('prendas_pedido')
                ->where('numero_pedido', '=', '')
                ->orWhereNull('numero_pedido')
                ->count();

            $this->warn("   ⚠️  Registros con numero_pedido vacío: $countVacios\n");

            if ($countVacios > 0) {
                // Eliminar registros vacíos
                DB::table('prendas_pedido')
                    ->where('numero_pedido', '=', '')
                    ->orWhereNull('numero_pedido')
                    ->delete();

                $this->line("   ✓ Registros eliminados: $countVacios\n");
            }

            // Verificar que todos tengan numero_pedido válido
            $countTotal = DB::table('prendas_pedido')->count();
            $countConNumero = DB::table('prendas_pedido')
                ->where('numero_pedido', '!=', '')
                ->whereNotNull('numero_pedido')
                ->count();

            $this->info("📊 ESTADO FINAL:");
            $this->line("   Total de prendas: $countTotal");
            $this->line("   Con numero_pedido válido: $countConNumero");

            if ($countTotal === $countConNumero) {
                $this->info("\n✅ Todos los registros tienen numero_pedido válido");
            } else {
                $this->warn("\n⚠️  Aún hay registros inválidos");
            }

            $this->info(str_repeat("=", 140) . "\n");

        } catch (\Exception $e) {
            $this->error("\n❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
