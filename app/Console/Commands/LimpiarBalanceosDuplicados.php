<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Prenda;
use App\Models\Balanceo;
use Illuminate\Support\Facades\DB;

class LimpiarBalanceosDuplicados extends Command
{
    protected $signature = 'balanceo:limpiar-duplicados {--dry-run : Simular sin eliminar}';
    protected $description = 'Eliminar prendas y balanceos duplicados';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info("🔍 Buscando duplicados...\n");

        // Buscar prendas duplicadas por nombre
        $duplicados = DB::table('prendas')
            ->select('nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('nombre')
            ->having('total', '>', 1)
            ->get();

        if ($duplicados->isEmpty()) {
            $this->info("✅ No se encontraron duplicados");
            return 0;
        }

        $this->warn("⚠️  Se encontraron " . $duplicados->count() . " prendas duplicadas\n");

        $totalEliminados = 0;

        foreach ($duplicados as $dup) {
            $this->line("📦 Prenda: {$dup->nombre} ({$dup->total} copias)");

            // Obtener todas las prendas con este nombre
            $prendas = Prenda::where('nombre', $dup->nombre)
                ->orderBy('id')
                ->get();

            // Mantener la primera, eliminar el resto
            $mantener = $prendas->first();
            $eliminar = $prendas->slice(1);

            $this->line("   ✓ Mantener: ID {$mantener->id}");
            
            foreach ($eliminar as $prenda) {
                $this->line("   ✗ Eliminar: ID {$prenda->id}");
                
                if (!$dryRun) {
                    // Eliminar balanceos asociados (cascade eliminará las operaciones)
                    $prenda->balanceos()->delete();
                    // Eliminar la prenda
                    $prenda->delete();
                }
                
                $totalEliminados++;
            }
        }

        if ($dryRun) {
            $this->warn("\n⚠️  Modo DRY-RUN: Se eliminarían {$totalEliminados} prendas duplicadas");
            $this->info("💡 Ejecuta sin --dry-run para eliminar realmente");
        } else {
            $this->info("\n✅ Se eliminaron {$totalEliminados} prendas duplicadas");
        }

        return 0;
    }
}
