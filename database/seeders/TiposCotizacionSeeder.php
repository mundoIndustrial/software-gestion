<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposCotizacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear tabla si no existe
        if (!DB::connection()->getSchemaBuilder()->hasTable('tipos_cotizacion')) {
            DB::statement('
                CREATE TABLE tipos_cotizacion (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    codigo VARCHAR(255) NOT NULL UNIQUE,
                    nombre VARCHAR(255) NOT NULL,
                    descripcion LONGTEXT NULL,
                    activo BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
        }

        // Insertar datos (sin truncate para evitar conflictos con claves foráneas)
        $tipos = [
            ['codigo' => 'M', 'nombre' => 'Muestra', 'descripcion' => 'Cotización de muestra', 'activo' => true],
            ['codigo' => 'P', 'nombre' => 'Prototipo', 'descripcion' => 'Cotización de prototipo', 'activo' => true],
            ['codigo' => 'G', 'nombre' => 'Grande', 'descripcion' => 'Cotización grande', 'activo' => true],
        ];

        foreach ($tipos as $tipo) {
            DB::table('tipos_cotizacion')->updateOrInsert(
                ['codigo' => $tipo['codigo']],
                $tipo
            );
        }
    }
}
