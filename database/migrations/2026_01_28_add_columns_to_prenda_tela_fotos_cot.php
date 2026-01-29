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
        // Verificar si la tabla existe
        if (Schema::hasTable('prenda_tela_fotos_cot')) {
            try {
                // Obtener la estructura actual de la tabla
                $columns = DB::select("DESCRIBE prenda_tela_fotos_cot");
                $columnNames = array_map(fn($col) => $col->Field, $columns);
                
                \Log::info('🔍 DEBUG: Columnas actuales en prenda_tela_fotos_cot', [
                    'columnas' => $columnNames,
                ]);
                
                // Buscar la columna con caracteres rotos (cualquier variación de tamaño)
                $tamanioColumn = null;
                foreach ($columnNames as $col) {
                    // Si encuentra algo que no sea las columnas conocidas y empiece con 'tam'
                    if (strpos($col, 'tam') === 0 && $col !== 'tamaño') {
                        $tamanioColumn = $col;
                        break;
                    }
                }
                
                if ($tamanioColumn) {
                    \Log::info('🔧 Renombrando columna corrupta', ['columna_actual' => $tamanioColumn]);
                    // Usar backticks para escapar caracteres especiales
                    DB::statement("ALTER TABLE `prenda_tela_fotos_cot` CHANGE COLUMN `$tamanioColumn` `tamaño` INT NULL COMMENT 'Tamaño del archivo en bytes'");
                    \Log::info('✅ Columna renombrada a tamaño correctamente');
                } elseif (in_array('tamaño', $columnNames)) {
                    \Log::info('✅ Columna tamaño ya existe y está correcta');
                } else {
                    \Log::warning('⚠️ Columna tamaño no encontrada en ninguna forma');
                }
                
            } catch (\Exception $e) {
                \Log::error('❌ Error durante migración', ['error' => $e->getMessage()]);
                throw $e;
            }
        } else {
            \Log::warning('⚠️ Tabla prenda_tela_fotos_cot no existe. Se saltará esta migración.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada en la reversión
        \Log::info('⏮️ Reversión de migración prenda_tela_fotos_cot');
    }
};
