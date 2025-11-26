<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RollbackProcessesMigration extends Command
{
    protected $signature = 'migrate:rollback-procesos';
    protected $description = 'Revierte la migración de procesos (elimina procesos creados en la migración)';

    public function handle()
    {
        $this->info("\n");
        $this->info(str_repeat("=", 100));
        $this->info("ROLLBACK: Eliminando procesos migrados");
        $this->info(str_repeat("=", 100) . "\n");

        if (!$this->confirm('¿Estás seguro de que deseas eliminar todos los procesos creados por la migración?')) {
            $this->info("Operación cancelada.");
            return 0;
        }

        try {
            // Obtener procesos que fueron creados (asumiendo que son los más recientes)
            $procesosEliminados = DB::table('procesos_prenda')
                ->whereIn('proceso', [
                    'Creación Orden', 'Inventario', 'Insumos y Telas', 'Corte', 
                    'Bordado', 'Estampado', 'Costura', 'Reflectivo', 'Lavandería', 
                    'Arreglos', 'Control Calidad', 'Entrega', 'Despacho'
                ])
                ->delete();

            $this->info("✅ Procesos eliminados: $procesosEliminados\n");
            $this->info(str_repeat("=", 100));
            $this->info("ROLLBACK COMPLETADO");
            $this->info(str_repeat("=", 100) . "\n");

        } catch (\Exception $e) {
            $this->error("❌ Error al hacer rollback: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
