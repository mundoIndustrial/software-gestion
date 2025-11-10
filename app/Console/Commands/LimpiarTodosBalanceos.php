<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Prenda;
use App\Models\Balanceo;
use App\Models\OperacionBalanceo;
use Illuminate\Support\Facades\DB;

class LimpiarTodosBalanceos extends Command
{
    protected $signature = 'balanceo:limpiar-todo {--force : Forzar eliminación sin confirmación}';
    protected $description = 'Eliminar TODOS los balanceos, operaciones y prendas';

    public function handle()
    {
        $force = $this->option('force');

        // Contar registros
        $totalPrendas = Prenda::count();
        $totalBalanceos = Balanceo::count();
        $totalOperaciones = OperacionBalanceo::count();

        $this->warn("⚠️  ADVERTENCIA: Esta acción eliminará TODOS los datos");
        $this->line("");
        $this->line("📊 Registros a eliminar:");
        $this->line("   • Prendas: {$totalPrendas}");
        $this->line("   • Balanceos: {$totalBalanceos}");
        $this->line("   • Operaciones: {$totalOperaciones}");
        $this->line("");

        if (!$force) {
            if (!$this->confirm('¿Estás seguro de que quieres eliminar TODO?')) {
                $this->info("❌ Operación cancelada");
                return 0;
            }
        }

        $this->info("🗑️  Eliminando todos los registros...\n");

        try {
            // Desactivar foreign key checks temporalmente
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Eliminar en orden correcto (por las foreign keys)
            $this->line("1️⃣ Eliminando operaciones...");
            DB::table('operaciones_balanceo')->truncate();
            $this->info("   ✅ {$totalOperaciones} operaciones eliminadas");

            $this->line("2️⃣ Eliminando balanceos...");
            DB::table('balanceos')->truncate();
            $this->info("   ✅ {$totalBalanceos} balanceos eliminados");

            $this->line("3️⃣ Eliminando prendas...");
            DB::table('prendas')->truncate();
            $this->info("   ✅ {$totalPrendas} prendas eliminadas");

            // Reactivar foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->line("");
            $this->info("✅ Todos los datos han sido eliminados exitosamente");
            $this->line("");
            $this->info("💡 Ahora puedes importar desde cero con:");
            $this->line("   php artisan balanceo:importar-excel archivo.xlsx");

            return 0;

        } catch (\Exception $e) {
            // Asegurarse de reactivar foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->error("❌ Error al eliminar: " . $e->getMessage());
            return 1;
        }
    }
}
