<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixRelacionesPorNumeroPedido extends Command
{
    protected $signature = 'fix:relaciones-numero-pedido {--dry-run : Simular sin guardar}';
    protected $description = 'Cambiar relaciones de prendas_pedido y procesos_prenda para usar numero_pedido en lugar de pedido_produccion_id';

    public function handle()
    {
        $this->info("\n" . str_repeat("=", 140));
        $this->info("🔧 REPARACIÓN DE RELACIONES: Cambiar a numero_pedido");
        $this->info(str_repeat("=", 140) . "\n");

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn("⚠️  MODO DRY-RUN: Los cambios NO se guardarán\n");
        }

        try {
            // PASO 1: Modificar tabla prendas_pedido
            $this->info("👕 PASO 1: Modificando tabla prendas_pedido...\n");
            $this->modificarPrendasPedido($dryRun);

            // PASO 2: Modificar tabla procesos_prenda
            $this->info("\n⚙️  PASO 2: Modificando tabla procesos_prenda...\n");
            $this->modificarProcesosPrenda($dryRun);

            // PASO 3: Verificar integridad
            $this->info("\n✅ PASO 3: Verificando integridad de datos...\n");
            $this->verificarIntegridad();

            $this->mostrarResumen($dryRun);

        } catch (\Exception $e) {
            $this->error("\n❌ Error: " . $e->getMessage());
            \Log::error('Error en reparación de relaciones: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Modificar tabla prendas_pedido
     */
    private function modificarPrendasPedido($dryRun)
    {
        if (!$dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // 1. Agregar columna numero_pedido si no existe
            if (!Schema::hasColumn('prendas_pedido', 'numero_pedido')) {
                $this->line("   ✓ Agregando columna numero_pedido a prendas_pedido");
                DB::statement('ALTER TABLE prendas_pedido ADD COLUMN numero_pedido VARCHAR(255) NULL AFTER id');
            }

            // 2. Rellenar numero_pedido desde pedido_produccion_id
            if (Schema::hasColumn('prendas_pedido', 'pedido_produccion_id')) {
                $this->line("   ✓ Rellenando numero_pedido desde pedido_produccion_id");
                DB::statement('
                    UPDATE prendas_pedido pp
                    JOIN pedidos_produccion p ON pp.pedido_produccion_id = p.id
                    SET pp.numero_pedido = p.numero_pedido
                    WHERE pp.numero_pedido IS NULL
                ');

                $countNull = DB::table('prendas_pedido')->whereNull('numero_pedido')->count();
                if ($countNull > 0) {
                    $this->warn("   ⚠️  Hay $countNull registros sin numero_pedido - rellenando con valor vacío");
                    DB::table('prendas_pedido')->whereNull('numero_pedido')->update(['numero_pedido' => '']);
                }
            }

            // 3. Hacer numero_pedido NOT NULL
            $this->line("   ✓ Configurando numero_pedido como NOT NULL");
            DB::statement('ALTER TABLE prendas_pedido MODIFY COLUMN numero_pedido VARCHAR(255) NOT NULL');

            // 4. Crear índice en numero_pedido
            $this->line("   ✓ Creando índice en numero_pedido");
            try {
                DB::statement('CREATE INDEX idx_prendas_numero_pedido ON prendas_pedido(numero_pedido)');
            } catch (\Exception $e) {
                $this->line("   ℹ️  Índice puede que ya exista");
            }

            // 5. Crear FK con numero_pedido
            $this->line("   ✓ Creando restricción de clave foránea con numero_pedido");
            try {
                DB::statement('
                    ALTER TABLE prendas_pedido
                    ADD CONSTRAINT fk_prendas_numero_pedido
                    FOREIGN KEY (numero_pedido)
                    REFERENCES pedidos_produccion(numero_pedido)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ');
            } catch (\Exception $e) {
                $this->line("   ℹ️  FK puede que ya exista: " . $e->getMessage());
            }

            // 6. Eliminar FK antigua si existe
            if (Schema::hasColumn('prendas_pedido', 'pedido_produccion_id')) {
                $this->line("   ✓ Eliminando restricción de clave foránea antigua");
                
                // Obtener las FK que existen
                $fks = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'prendas_pedido' AND COLUMN_NAME = 'pedido_produccion_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL");
                
                foreach ($fks as $fk) {
                    try {
                        DB::statement('ALTER TABLE prendas_pedido DROP FOREIGN KEY ' . $fk->CONSTRAINT_NAME);
                        $this->line("   ✓ FK eliminada: " . $fk->CONSTRAINT_NAME);
                    } catch (\Exception $e) {
                        $this->line("   ℹ️  Error eliminando FK: " . $e->getMessage());
                    }
                }

                // 7. Eliminar columna pedido_produccion_id
                $this->line("   ✓ Eliminando columna pedido_produccion_id");
                DB::statement('ALTER TABLE prendas_pedido DROP COLUMN pedido_produccion_id');
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->line("   ✅ Tabla prendas_pedido modificada correctamente");
        } else {
            $this->line("   [DRY-RUN] Se modificaría prendas_pedido para usar numero_pedido");
        }
    }

    /**
     * Modificar tabla procesos_prenda
     */
    private function modificarProcesosPrenda($dryRun)
    {
        if (!$dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // 1. Agregar columna numero_pedido si no existe
            if (!Schema::hasColumn('procesos_prenda', 'numero_pedido')) {
                $this->line("   ✓ Agregando columna numero_pedido a procesos_prenda");
                DB::statement('ALTER TABLE procesos_prenda ADD COLUMN numero_pedido VARCHAR(255) NULL AFTER id');
            }

            // 2. Rellenar numero_pedido desde pedido_produccion_id si existe
            if (Schema::hasColumn('procesos_prenda', 'pedido_produccion_id')) {
                $this->line("   ✓ Rellenando numero_pedido desde pedido_produccion_id");
                DB::statement('
                    UPDATE procesos_prenda pp
                    JOIN pedidos_produccion p ON pp.pedido_produccion_id = p.id
                    SET pp.numero_pedido = p.numero_pedido
                    WHERE pp.numero_pedido IS NULL
                ');

                $countNull = DB::table('procesos_prenda')->whereNull('numero_pedido')->count();
                if ($countNull > 0) {
                    $this->warn("   ⚠️  Hay $countNull registros sin numero_pedido - rellenando con valor vacío");
                    DB::table('procesos_prenda')->whereNull('numero_pedido')->update(['numero_pedido' => '']);
                }

                // 3. Eliminar FK antigua si existe
                $this->line("   ✓ Eliminando restricción de clave foránea antigua");
                
                // Obtener las FK que existen
                $fks = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'procesos_prenda' AND COLUMN_NAME = 'pedido_produccion_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL");
                
                foreach ($fks as $fk) {
                    try {
                        DB::statement('ALTER TABLE procesos_prenda DROP FOREIGN KEY ' . $fk->CONSTRAINT_NAME);
                        $this->line("   ✓ FK eliminada: " . $fk->CONSTRAINT_NAME);
                    } catch (\Exception $e) {
                        $this->line("   ℹ️  Error eliminando FK: " . $e->getMessage());
                    }
                }

                // 4. Eliminar columna pedido_produccion_id
                $this->line("   ✓ Eliminando columna pedido_produccion_id");
                DB::statement('ALTER TABLE procesos_prenda DROP COLUMN pedido_produccion_id');
            }

            // 5. Hacer numero_pedido NOT NULL
            $this->line("   ✓ Configurando numero_pedido como NOT NULL");
            DB::statement('ALTER TABLE procesos_prenda MODIFY COLUMN numero_pedido VARCHAR(255) NOT NULL');

            // 6. Crear índice en numero_pedido
            $this->line("   ✓ Creando índice en numero_pedido");
            try {
                DB::statement('CREATE INDEX idx_procesos_numero_pedido ON procesos_prenda(numero_pedido)');
            } catch (\Exception $e) {
                $this->line("   ℹ️  Índice puede que ya exista");
            }

            // 7. Crear FK con numero_pedido
            $this->line("   ✓ Creando restricción de clave foránea con numero_pedido");
            try {
                DB::statement('
                    ALTER TABLE procesos_prenda
                    ADD CONSTRAINT fk_procesos_numero_pedido
                    FOREIGN KEY (numero_pedido)
                    REFERENCES pedidos_produccion(numero_pedido)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ');
            } catch (\Exception $e) {
                $this->line("   ℹ️  FK puede que ya exista: " . $e->getMessage());
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->line("   ✅ Tabla procesos_prenda modificada correctamente");
        } else {
            $this->line("   [DRY-RUN] Se modificaría procesos_prenda para usar numero_pedido");
        }
    }

    /**
     * Verificar integridad de datos
     */
    private function verificarIntegridad()
    {
        $this->line("   📊 Verificando integridad de datos...\n");

        // Contar prendas_pedido
        $countPrendas = DB::table('prendas_pedido')->count();
        $this->line("   ✓ Total de prendas: $countPrendas");

        // Contar procesos_prenda
        $countProcesos = DB::table('procesos_prenda')->count();
        $this->line("   ✓ Total de procesos: $countProcesos");

        // Verificar que no haya numero_pedido inválidos en prendas
        $prendasInvalidas = DB::table('prendas_pedido as pp')
            ->leftJoin('pedidos_produccion as p', 'pp.numero_pedido', '=', 'p.numero_pedido')
            ->whereNull('p.id')
            ->count();

        if ($prendasInvalidas > 0) {
            $this->warn("   ⚠️  Hay $prendasInvalidas prendas con numero_pedido inválido");
        } else {
            $this->line("   ✓ Todas las prendas tienen numero_pedido válido");
        }

        // Verificar que no haya numero_pedido inválidos en procesos
        $procesosInvalidos = DB::table('procesos_prenda as pp')
            ->leftJoin('pedidos_produccion as p', 'pp.numero_pedido', '=', 'p.numero_pedido')
            ->whereNull('p.id')
            ->count();

        if ($procesosInvalidos > 0) {
            $this->warn("   ⚠️  Hay $procesosInvalidos procesos con numero_pedido inválido");
        } else {
            $this->line("   ✓ Todos los procesos tienen numero_pedido válido");
        }

        $this->newLine();
    }

    /**
     * Mostrar resumen
     */
    private function mostrarResumen($dryRun)
    {
        $this->info("\n" . str_repeat("=", 140));
        $this->info("📋 RESUMEN DE CAMBIOS");
        $this->info(str_repeat("=", 140));

        $resumen = [
            ['Tabla', 'Cambio'],
            ['prendas_pedido', 'Cambiada FK a numero_pedido (eliminado pedido_produccion_id)'],
            ['procesos_prenda', 'Cambiada FK a numero_pedido (eliminado pedido_produccion_id)'],
        ];

        $this->table(
            ['Tabla', 'Cambio'],
            array_slice($resumen, 1)
        );

        if ($dryRun) {
            $this->warn("\n⚠️  MODO DRY-RUN: Los cambios NO fueron guardados");
            $this->info("Ejecuta sin --dry-run para aplicar los cambios\n");
        } else {
            $this->info("\n✅ RELACIONES REPARADAS EXITOSAMENTE");
            $this->info("Las tablas prendas_pedido y procesos_prenda ahora se relacionan por numero_pedido\n");
        }

        $this->info(str_repeat("=", 140) . "\n");
    }
}
