<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixTiposDatosNumeroPedido extends Command
{
    protected $signature = 'fix:tipos-numero-pedido';
    protected $description = 'Arreglar tipos de datos y crear FK correctamente para numero_pedido';

    public function handle()
    {
        $this->info("\n" . str_repeat("=", 140));
        $this->info("🔧 REPARACIÓN DE TIPOS DE DATOS: numero_pedido");
        $this->info(str_repeat("=", 140) . "\n");

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // PASO 1: Verificar y estandarizar tipos de datos
            $this->info("👁️  PASO 1: Verificando tipos de datos...\n");

            // Obtener tipo de numero_pedido en pedidos_produccion
            $tipoPedidos = DB::select("
                SELECT COLUMN_TYPE 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = 'pedidos_produccion' 
                AND COLUMN_NAME = 'numero_pedido'
            ");

            if (!empty($tipoPedidos)) {
                $tipo = $tipoPedidos[0]->COLUMN_TYPE;
                $this->line("   ✓ Tipo en pedidos_produccion: $tipo");
            }

            // PASO 2: Actualizar tipos en prendas_pedido
            $this->info("\n👕 PASO 2: Actualizando tipo en prendas_pedido...\n");
            
            try {
                DB::statement('ALTER TABLE prendas_pedido MODIFY COLUMN numero_pedido VARCHAR(255) NOT NULL');
                $this->line("   ✓ Tipo actualizado: VARCHAR(255)");
            } catch (\Exception $e) {
                $this->error("   ❌ Error: " . $e->getMessage());
            }

            // PASO 3: Actualizar tipos en procesos_prenda
            $this->info("\n⚙️  PASO 3: Actualizando tipo en procesos_prenda...\n");
            
            try {
                DB::statement('ALTER TABLE procesos_prenda MODIFY COLUMN numero_pedido VARCHAR(255) NOT NULL');
                $this->line("   ✓ Tipo actualizado: VARCHAR(255)");
            } catch (\Exception $e) {
                $this->error("   ❌ Error: " . $e->getMessage());
            }

            // PASO 4: Eliminar FKs incompatibles y crear nuevas
            $this->info("\n🔗 PASO 4: Reconstruyendo claves foráneas...\n");

            // Para prendas_pedido
            $this->line("   Procesando prendas_pedido...");
            
            // Obtener FK existente
            $fks = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'prendas_pedido' AND COLUMN_NAME = 'numero_pedido' 
                AND REFERENCED_TABLE_NAME IS NOT NULL");
            
            foreach ($fks as $fk) {
                try {
                    DB::statement('ALTER TABLE prendas_pedido DROP FOREIGN KEY ' . $fk->CONSTRAINT_NAME);
                    $this->line("   ✓ FK antigua eliminada: " . $fk->CONSTRAINT_NAME);
                } catch (\Exception $e) {
                    $this->line("   ℹ️  FK no existe o no se pudo eliminar");
                }
            }

            // Crear nueva FK
            try {
                DB::statement('
                    ALTER TABLE prendas_pedido
                    ADD CONSTRAINT fk_prendas_numero_pedido_new
                    FOREIGN KEY (numero_pedido)
                    REFERENCES pedidos_produccion(numero_pedido)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ');
                $this->line("   ✓ FK nueva creada: fk_prendas_numero_pedido_new");
            } catch (\Exception $e) {
                $this->error("   ❌ Error creando FK: " . $e->getMessage());
            }

            // Para procesos_prenda
            $this->line("\n   Procesando procesos_prenda...");
            
            $fks = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'procesos_prenda' AND COLUMN_NAME = 'numero_pedido' 
                AND REFERENCED_TABLE_NAME IS NOT NULL");
            
            foreach ($fks as $fk) {
                try {
                    DB::statement('ALTER TABLE procesos_prenda DROP FOREIGN KEY ' . $fk->CONSTRAINT_NAME);
                    $this->line("   ✓ FK antigua eliminada: " . $fk->CONSTRAINT_NAME);
                } catch (\Exception $e) {
                    $this->line("   ℹ️  FK no existe o no se pudo eliminar");
                }
            }

            // Crear nueva FK
            try {
                DB::statement('
                    ALTER TABLE procesos_prenda
                    ADD CONSTRAINT fk_procesos_numero_pedido_new
                    FOREIGN KEY (numero_pedido)
                    REFERENCES pedidos_produccion(numero_pedido)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ');
                $this->line("   ✓ FK nueva creada: fk_procesos_numero_pedido_new");
            } catch (\Exception $e) {
                $this->error("   ❌ Error creando FK: " . $e->getMessage());
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->info("\n" . str_repeat("=", 140));
            $this->info("✅ TIPOS DE DATOS Y FK REPARADOS EXITOSAMENTE");
            $this->info(str_repeat("=", 140) . "\n");

        } catch (\Exception $e) {
            $this->error("\n❌ Error: " . $e->getMessage());
            \Log::error('Error en reparación de tipos: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
