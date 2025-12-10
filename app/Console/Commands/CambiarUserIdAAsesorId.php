<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CambiarUserIdAAsesorId extends Command
{
    protected $signature = 'db:cambiar-user-id-a-asesor-id';
    protected $description = 'Cambia la columna user_id a asesor_id en tabla cotizaciones';

    public function handle()
    {
        $this->info('🔄 CAMBIANDO user_id A asesor_id EN TABLA cotizaciones');
        $this->newLine();

        try {
            // Verificar si la columna user_id existe
            $columnas = Schema::getColumns('cotizaciones');
            $tieneUserId = false;
            $tieneAsesorId = false;

            foreach ($columnas as $col) {
                if ($col['name'] === 'user_id') {
                    $tieneUserId = true;
                }
                if ($col['name'] === 'asesor_id') {
                    $tieneAsesorId = true;
                }
            }

            if (!$tieneUserId) {
                $this->warn('⚠️ La columna user_id no existe');
                return;
            }

            if ($tieneAsesorId) {
                $this->warn('⚠️ La columna asesor_id ya existe');
                return;
            }

            // Paso 1: Agregar columna asesor_id
            $this->line('📋 Paso 1: Agregando columna asesor_id...');
            DB::statement('
                ALTER TABLE cotizaciones 
                ADD COLUMN asesor_id BIGINT UNSIGNED NULL AFTER id
            ');
            $this->info('   ✅ Columna asesor_id agregada');

            // Paso 2: Copiar datos de user_id a asesor_id
            $this->line('📋 Paso 2: Copiando datos de user_id a asesor_id...');
            DB::statement('UPDATE cotizaciones SET asesor_id = user_id');
            $this->info('   ✅ Datos copiados');

            // Paso 3: Eliminar FK antigua
            $this->line('📋 Paso 3: Eliminando Foreign Key antigua...');
            try {
                DB::statement('
                    ALTER TABLE cotizaciones 
                    DROP FOREIGN KEY cotizaciones_user_id_foreign
                ');
                $this->info('   ✅ Foreign Key eliminada');
            } catch (\Exception $e) {
                $this->warn('   ⚠️ Foreign Key no encontrada, continuando...');
            }

            // Paso 4: Agregar FK nueva
            $this->line('📋 Paso 4: Agregando Foreign Key nueva...');
            DB::statement('
                ALTER TABLE cotizaciones 
                ADD CONSTRAINT cotizaciones_asesor_id_foreign 
                FOREIGN KEY (asesor_id) 
                REFERENCES users(id) 
                ON DELETE SET NULL
            ');
            $this->info('   ✅ Foreign Key nueva agregada');

            // Paso 5: Eliminar columna user_id
            $this->line('📋 Paso 5: Eliminando columna user_id...');
            DB::statement('ALTER TABLE cotizaciones DROP COLUMN user_id');
            $this->info('   ✅ Columna user_id eliminada');

            // Paso 6: Agregar índice
            $this->line('📋 Paso 6: Agregando índice...');
            try {
                DB::statement('
                    ALTER TABLE cotizaciones 
                    ADD INDEX idx_asesor_id (asesor_id)
                ');
                $this->info('   ✅ Índice agregado');
            } catch (\Exception $e) {
                $this->warn('   ⚠️ Índice ya existe');
            }

            $this->newLine();
            $this->info('✅ CAMBIO COMPLETADO EXITOSAMENTE');
            $this->newLine();

            // Verificación
            $this->line('📊 VERIFICACIÓN:');
            $columnas = Schema::getColumns('cotizaciones');
            foreach ($columnas as $col) {
                if ($col['name'] === 'asesor_id') {
                    $this->line("   ✅ Columna asesor_id: {$col['type']}");
                }
            }

            $cantidad = DB::table('cotizaciones')->count();
            $this->line("   📈 Total de cotizaciones: {$cantidad}");

            $conAsesor = DB::table('cotizaciones')->whereNotNull('asesor_id')->count();
            $this->line("   ✅ Cotizaciones con asesor_id: {$conAsesor}");

        } catch (\Exception $e) {
            $this->error('❌ ERROR: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
