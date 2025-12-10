<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EjecutarMigracionImagenes extends Command
{
    protected $signature = 'db:ejecutar-migracion-imagenes';
    protected $description = 'Ejecuta la migración completa de imágenes (tablas, datos, modificaciones)';

    public function handle()
    {
        $this->info('🚀 INICIANDO MIGRACIÓN COMPLETA DE IMÁGENES');
        $this->newLine();

        try {
            // PASO 1: Crear nuevas tablas
            $this->paso1_crearTablas();

            // PASO 2: Migrar datos de telas
            $this->paso2_migrarTelas();

            // PASO 3: Migrar imágenes de logos
            $this->paso3_migrarLogos();

            // PASO 4: Modificar tablas existentes
            $this->paso4_modificarTablas();

            // PASO 5: Verificación final
            $this->paso5_verificacion();

            $this->newLine();
            $this->info('✅ MIGRACIÓN COMPLETADA EXITOSAMENTE');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ ERROR EN MIGRACIÓN: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    private function paso1_crearTablas()
    {
        $this->info('📋 PASO 1: Creando nuevas tablas...');

        // Crear tabla prenda_tela_fotos_cot
        DB::statement('
            CREATE TABLE IF NOT EXISTS prenda_tela_fotos_cot (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                prenda_cot_id BIGINT UNSIGNED NOT NULL,
                ruta_original VARCHAR(500),
                ruta_webp VARCHAR(500),
                ruta_miniatura VARCHAR(500),
                orden INT DEFAULT 0,
                ancho INT,
                alto INT,
                tamaño INT,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_prenda_cot_id (prenda_cot_id),
                INDEX idx_orden (orden),
                CONSTRAINT fk_prenda_tela_fotos_prenda_cot 
                    FOREIGN KEY (prenda_cot_id) 
                    REFERENCES prendas_cot(id) 
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $this->line('   ✅ Tabla prenda_tela_fotos_cot creada');

        // Crear tabla logo_fotos_cot
        DB::statement('
            CREATE TABLE IF NOT EXISTS logo_fotos_cot (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                logo_cotizacion_id BIGINT UNSIGNED NOT NULL,
                ruta_original VARCHAR(500),
                ruta_webp VARCHAR(500),
                ruta_miniatura VARCHAR(500),
                orden INT DEFAULT 0,
                ancho INT,
                alto INT,
                tamaño INT,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_logo_cotizacion_id (logo_cotizacion_id),
                INDEX idx_orden (orden),
                CONSTRAINT fk_logo_fotos_logo_cotizacion 
                    FOREIGN KEY (logo_cotizacion_id) 
                    REFERENCES logo_cotizaciones(id) 
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $this->line('   ✅ Tabla logo_fotos_cot creada');
    }

    private function paso2_migrarTelas()
    {
        $this->info('📋 PASO 2: Migrando fotos de telas...');

        // Verificar si la columna 'tipo' existe
        $columnas = DB::select("DESCRIBE prenda_fotos_cot");
        $tieneColumna = false;

        foreach ($columnas as $col) {
            if ($col->Field === 'tipo') {
                $tieneColumna = true;
                break;
            }
        }

        if (!$tieneColumna) {
            $this->line('   ℹ️ Columna "tipo" no existe, fotos de telas ya fueron migradas o no existen');
            return;
        }

        $cantidad = DB::table('prenda_fotos_cot')
            ->where('tipo', 'tela')
            ->count();

        if ($cantidad === 0) {
            $this->line('   ℹ️ No hay fotos de telas para migrar');
            return;
        }

        DB::statement('
            INSERT INTO prenda_tela_fotos_cot (
                prenda_cot_id,
                ruta_original,
                ruta_webp,
                ruta_miniatura,
                orden,
                ancho,
                alto,
                tamaño,
                created_at,
                updated_at
            )
            SELECT 
                prenda_cot_id,
                ruta_original,
                ruta_webp,
                ruta_miniatura,
                orden,
                ancho,
                alto,
                tamaño,
                created_at,
                updated_at
            FROM prenda_fotos_cot
            WHERE tipo = "tela"
        ');

        $this->line("   ✅ {$cantidad} foto(s) de telas migrada(s)");
    }

    private function paso3_migrarLogos()
    {
        $this->info('📋 PASO 3: Migrando imágenes de logos...');

        $logos = DB::table('logo_cotizaciones')
            ->whereNotNull('imagenes')
            ->where('imagenes', '!=', 'null')
            ->get();

        $totalMigradas = 0;
        $errores = 0;

        foreach ($logos as $logo) {
            try {
                $imagenes = json_decode($logo->imagenes, true);

                if (!is_array($imagenes)) {
                    $errores++;
                    continue;
                }

                // Máximo 5 imágenes
                if (count($imagenes) > 5) {
                    $imagenes = array_slice($imagenes, 0, 5);
                }

                foreach ($imagenes as $orden => $ruta) {
                    if (empty($ruta)) {
                        continue;
                    }

                    DB::table('logo_fotos_cot')->insert([
                        'logo_cotizacion_id' => $logo->id,
                        'ruta_original' => $ruta,
                        'ruta_webp' => $ruta,
                        'ruta_miniatura' => null,
                        'orden' => $orden,
                        'ancho' => null,
                        'alto' => null,
                        'tamaño' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $totalMigradas++;
                }
            } catch (\Exception $e) {
                $errores++;
            }
        }

        $this->line("   ✅ {$totalMigradas} imagen(es) de logo(s) migrada(s)");
        if ($errores > 0) {
            $this->warn("   ⚠️ {$errores} error(es) durante migración");
        }
    }

    private function paso4_modificarTablas()
    {
        $this->info('📋 PASO 4: Modificando tablas existentes...');

        // Verificar si la columna 'tipo' existe antes de eliminarla
        $columnas = DB::select("DESCRIBE prenda_fotos_cot");
        $tieneColumna = false;

        foreach ($columnas as $col) {
            if ($col->Field === 'tipo') {
                $tieneColumna = true;
                break;
            }
        }

        if ($tieneColumna) {
            DB::statement('ALTER TABLE prenda_fotos_cot DROP COLUMN tipo');
            $this->line('   ✅ Columna "tipo" eliminada de prenda_fotos_cot');
        } else {
            $this->line('   ℹ️ Columna "tipo" ya no existe en prenda_fotos_cot');
        }

        // Modificar prenda_telas_cot
        $columnas = DB::select("DESCRIBE prenda_telas_cot");
        $tieneVariante = false;
        $tienePrenda = false;

        foreach ($columnas as $col) {
            if ($col->Field === 'variante_prenda_cot_id') {
                $tieneVariante = true;
            }
            if ($col->Field === 'prenda_cot_id') {
                $tienePrenda = true;
            }
        }

        if ($tieneVariante && !$tienePrenda) {
            // Agregar columna prenda_cot_id
            DB::statement('
                ALTER TABLE prenda_telas_cot 
                ADD COLUMN prenda_cot_id BIGINT UNSIGNED NULL AFTER id
            ');

            // Copiar datos
            DB::statement('
                UPDATE prenda_telas_cot pt
                INNER JOIN prenda_variantes_cot pv ON pt.variante_prenda_cot_id = pv.id
                SET pt.prenda_cot_id = pv.prenda_cot_id
            ');

            // Eliminar FK antigua (si existe)
            try {
                DB::statement('
                    ALTER TABLE prenda_telas_cot 
                    DROP FOREIGN KEY prenda_telas_cot_variante_prenda_cot_id_foreign
                ');
            } catch (\Exception $e) {
                $this->warn('   ⚠️ FK antigua no encontrada, continuando...');
            }

            // Eliminar columna antigua
            DB::statement('
                ALTER TABLE prenda_telas_cot 
                DROP COLUMN variante_prenda_cot_id
            ');

            // Agregar FK nueva
            DB::statement('
                ALTER TABLE prenda_telas_cot 
                ADD CONSTRAINT fk_prenda_telas_prenda_cot 
                FOREIGN KEY (prenda_cot_id) 
                REFERENCES prendas_cot(id) 
                ON DELETE CASCADE
            ');

            // Hacer NOT NULL
            DB::statement('
                ALTER TABLE prenda_telas_cot 
                MODIFY COLUMN prenda_cot_id BIGINT UNSIGNED NOT NULL
            ');

            $this->line('   ✅ Tabla prenda_telas_cot modificada');
        } else {
            $this->line('   ℹ️ Tabla prenda_telas_cot ya está modificada');
        }

        // Agregar índices
        try {
            DB::statement('ALTER TABLE prenda_fotos_cot ADD INDEX idx_prenda_cot_id (prenda_cot_id)');
        } catch (\Exception $e) {
            // Índice ya existe
        }

        try {
            DB::statement('ALTER TABLE prenda_fotos_cot ADD INDEX idx_orden (orden)');
        } catch (\Exception $e) {
            // Índice ya existe
        }

        $this->line('   ✅ Índices verificados en prenda_fotos_cot');
    }

    private function paso5_verificacion()
    {
        $this->info('📋 PASO 5: Verificación final...');

        $stats = DB::select('
            SELECT 
                "prenda_fotos_cot" as tabla,
                COUNT(*) as cantidad
            FROM prenda_fotos_cot
            UNION ALL
            SELECT 
                "prenda_tela_fotos_cot" as tabla,
                COUNT(*) as cantidad
            FROM prenda_tela_fotos_cot
            UNION ALL
            SELECT 
                "logo_fotos_cot" as tabla,
                COUNT(*) as cantidad
            FROM logo_fotos_cot
        ');

        $this->line('   📊 Registros por tabla:');
        foreach ($stats as $stat) {
            $this->line("      • {$stat->tabla}: {$stat->cantidad}");
        }
    }
}
