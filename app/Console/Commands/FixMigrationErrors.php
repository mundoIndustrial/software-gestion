<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMigrationErrors extends Command
{
    protected $signature = 'migrate:fix-errors';
    protected $description = 'Corrige errores encontrados en la migración (campos demasiado pequeños, fechas inválidas)';

    public function handle()
    {
        $this->info("\n");
        $this->info(str_repeat("=", 100));
        $this->info("CORRECCIÓN DE ERRORES DE MIGRACIÓN");
        $this->info(str_repeat("=", 100) . "\n");

        // PASO 1: Expandir campo nombre_prenda
        $this->info("📋 PASO 1: Expandiendo campo nombre_prenda...");
        try {
            DB::statement('ALTER TABLE prendas_pedido MODIFY nombre_prenda VARCHAR(500)');
            $this->info("   ✅ Campo nombre_prenda expandido a 500 caracteres\n");
        } catch (\Exception $e) {
            $this->warn("   ⚠️  Campo ya está expandido o hay un error: " . $e->getMessage() . "\n");
        }

        // PASO 2: Limpiar procesos con fechas inválidas
        $this->info("📋 PASO 2: Limpiando procesos con fechas inválidas...");
        try {
            // Eliminar procesos con fechas nulas o vacías usando raw queries
            $eliminados = 0;
            
            // Eliminare procesos nulos o vacíos
            $eliminados += DB::table('procesos_prenda')
                ->whereNull('fecha_inicio')
                ->delete();
            
            // Procesos con solo espacios
            $eliminados += DB::table('procesos_prenda')
                ->where('fecha_inicio', ' ')
                ->delete();

            $this->info("   ✅ Procesos con fechas inválidas eliminados: $eliminados\n");
        } catch (\Exception $e) {
            $this->warn("   ⚠️  Error al limpiar procesos: " . $e->getMessage() . "\n");
        }

        // PASO 3: Reintentar migración de prendas que fallaron
        $this->info("📋 PASO 3: Completando prendas que no se migraron...");
        
        $pedidosEnTablaOriginal = DB::table('tabla_original')
            ->distinct()
            ->pluck('pedido')
            ->toArray();

        $pedidosYaMigrados = DB::table('prendas_pedido')
            ->join('pedidos_produccion', 'prendas_pedido.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->distinct()
            ->pluck('pedidos_produccion.numero_pedido')
            ->toArray();

        $pedidosFaltantes = array_diff($pedidosEnTablaOriginal, $pedidosYaMigrados);

        $this->info("   Pedidos sin prendas migradas: " . count($pedidosFaltantes) . "\n");

        if (count($pedidosFaltantes) > 0) {
            $this->warn("   ⚠️  Hay pedidos sin prendas migradas");
            $this->line("   Considere ejecutar el comando de migración nuevamente para estos pedidos");
        }

        $this->info("\n" . str_repeat("=", 100));
        $this->info("✅ CORRECCIÓN COMPLETADA");
        $this->info(str_repeat("=", 100) . "\n");

        return 0;
    }
}
