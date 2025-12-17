<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Limpiar prefijo 'storage/' de las rutas en todas las tablas de fotos
        // Para prevenir URLs duplicadas como /storage/storage/cotizaciones/...
        
        $tables = [
            'logo_fotos_cot' => ['ruta_original', 'ruta_webp', 'ruta_miniatura'],
            'prenda_fotos_cot' => ['ruta_original', 'ruta_webp', 'ruta_miniatura'],
            'prenda_tela_fotos_cot' => ['ruta_original', 'ruta_webp', 'ruta_miniatura'],
            'reflectivo_fotos_cotizacion' => ['ruta_original', 'ruta_webp'],
        ];

        foreach ($tables as $table => $columns) {
            // Verificar que la tabla existe
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                // Verificar que la columna existe
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                // Actualizar rutas que comienzan con 'storage/'
                DB::table($table)
                    ->where($column, 'LIKE', 'storage/%')
                    ->update([
                        $column => DB::raw("SUBSTRING({$column}, 9)") // Remover 'storage/' (9 caracteres)
                    ]);

                // También limpiar rutas que comienzan con '/storage/'
                DB::table($table)
                    ->where($column, 'LIKE', '/storage/%')
                    ->update([
                        $column => DB::raw("SUBSTRING({$column}, 10)") // Remover '/storage/' (10 caracteres)
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertir - el cambio es directional
    }
};
