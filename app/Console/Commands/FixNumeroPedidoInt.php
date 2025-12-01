<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixNumeroPedidoInt extends Command
{
    protected $signature = 'fix:numero-pedido-int';
    protected $description = 'Convertir numero_pedido a INT UNSIGNED en todas las tablas';

    public function handle()
    {
        $this->info("\n" . str_repeat("=", 140));
        $this->info("🔧 CONVERSIÓN DE TIPOS: numero_pedido a INT UNSIGNED");
        $this->info(str_repeat("=", 140) . "\n");

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // PASO 1: Convertir prendas_pedido
            $this->info("👕 PASO 1: Convertiendo prendas_pedido...\n");
            
            // Primero eliminar FK si existe
            $fks = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'prendas_pedido' AND COLUMN_NAME = 'numero_pedido' 
                AND REFERENCED_TABLE_NAME IS NOT NULL");
            
            foreach ($fks as $fk) {
                try {
                    DB::statement('ALTER TABLE prendas_pedido DROP FOREIGN KEY ' . $fk->CONSTRAINT_NAME);
                    $this->line("   ✓ FK eliminada: " . $fk->CONSTRAINT_NAME);
                } catch (\Exception $e) {
                    $this->line("   ℹ️  FK no existe");
                }
            }

            // Convertir columna a INT UNSIGNED
            try {
                DB::statement('ALTER TABLE prendas_pedido MODIFY COLUMN numero_pedido INT UNSIGNED NOT NULL');
                $this->line("   ✓ Columna convertida a INT UNSIGNED");
            } catch (\Exception $e) {
                $this->error("   ❌ Error: " . $e->getMessage());
            }

            // PASO 2: Convertir procesos_prenda
            $this->info("\n⚙️  PASO 2: Convertiendo procesos_prenda...\n");
            
            $fks = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'procesos_prenda' AND COLUMN_NAME = 'numero_pedido' 
                AND REFERENCED_TABLE_NAME IS NOT NULL");
            
            foreach ($fks as $fk) {
                try {
                    DB::statement('ALTER TABLE procesos_prenda DROP FOREIGN KEY ' . $fk->CONSTRAINT_NAME);
                    $this->line("   ✓ FK eliminada: " . $fk->CONSTRAINT_NAME);
                } catch (\Exception $e) {
                    $this->line("   ℹ️  FK no existe");
                }
            }

            // Convertir columna a INT UNSIGNED
            try {
                DB::statement('ALTER TABLE procesos_prenda MODIFY COLUMN numero_pedido INT UNSIGNED NOT NULL');
                $this->line("   ✓ Columna convertida a INT UNSIGNED");
            } catch (\Exception $e) {
                $this->error("   ❌ Error: " . $e->getMessage());
            }

            // PASO 3: Crear FK correctamente
            $this->info("\n🔗 PASO 3: Creando claves foráneas...\n");

            try {
                DB::statement('
                    ALTER TABLE prendas_pedido
                    ADD CONSTRAINT fk_prendas_numero_pedido
                    FOREIGN KEY (numero_pedido)
                    REFERENCES pedidos_produccion(numero_pedido)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ');
                $this->line("   ✓ FK creada para prendas_pedido");
            } catch (\Exception $e) {
                $this->error("   ❌ Error en prendas_pedido: " . $e->getMessage());
            }

            try {
                DB::statement('
                    ALTER TABLE procesos_prenda
                    ADD CONSTRAINT fk_procesos_numero_pedido
                    FOREIGN KEY (numero_pedido)
                    REFERENCES pedidos_produccion(numero_pedido)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ');
                $this->line("   ✓ FK creada para procesos_prenda");
            } catch (\Exception $e) {
                $this->error("   ❌ Error en procesos_prenda: " . $e->getMessage());
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->info("\n" . str_repeat("=", 140));
            $this->info("✅ CONVERSIÓN COMPLETADA EXITOSAMENTE");
            $this->info("Las tablas ahora se relacionan correctamente por numero_pedido (INT UNSIGNED)");
            $this->info(str_repeat("=", 140) . "\n");

        } catch (\Exception $e) {
            $this->error("\n❌ Error: " . $e->getMessage());
            \Log::error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
