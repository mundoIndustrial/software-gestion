<?php

namespace App\Console\Commands;

use App\Models\Balanceo;
use App\Models\Prenda;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReimportarBalanceo extends Command
{
    protected $signature = 'balanceo:reimportar {id : ID del balanceo a eliminar y reimportar}';
    protected $description = 'Elimina un balanceo existente para poder reimportarlo desde Excel';

    public function handle()
    {
        $balanceoId = $this->argument('id');
        
        $balanceo = Balanceo::with('prenda')->find($balanceoId);
        
        if (!$balanceo) {
            $this->error("Balanceo con ID {$balanceoId} no encontrado.");
            return 1;
        }

        $prendaNombre = $balanceo->prenda->nombre ?? 'Sin nombre';
        $prendaId = $balanceo->prenda_id;
        
        $this->warn("⚠️  ADVERTENCIA: Esto eliminará el balanceo y la prenda:");
        $this->line("   Balanceo ID: {$balanceoId}");
        $this->line("   Prenda: {$prendaNombre}");
        $this->line("   Prenda ID: {$prendaId}");
        
        if (!$this->confirm('¿Estás seguro de continuar?', false)) {
            $this->info('Operación cancelada.');
            return 0;
        }

        DB::beginTransaction();
        try {
            // Eliminar operaciones del balanceo
            $cantidadOps = $balanceo->operaciones()->count();
            $balanceo->operaciones()->delete();
            $this->info("✓ {$cantidadOps} operaciones eliminadas");
            
            // Eliminar balanceo
            $balanceo->delete();
            $this->info("✓ Balanceo eliminado");
            
            // Eliminar prenda
            if ($balanceo->prenda) {
                $balanceo->prenda->delete();
                $this->info("✓ Prenda eliminada");
            }
            
            DB::commit();
            
            $this->newLine();
            $this->info("✅ Balanceo eliminado exitosamente.");
            $this->line("Ahora puedes reimportar el archivo Excel con:");
            $this->line("   php artisan balanceo:importar <ruta-al-archivo.xlsx>");
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error al eliminar: " . $e->getMessage());
            return 1;
        }
    }
}
