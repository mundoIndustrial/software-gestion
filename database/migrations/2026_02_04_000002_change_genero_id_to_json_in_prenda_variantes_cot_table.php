<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero eliminar el índice
        DB::statement('ALTER TABLE prenda_variantes_cot DROP INDEX prenda_variantes_cot_genero_id_foreign');
        
        // Luego cambiar la columna a JSON
        DB::statement('ALTER TABLE prenda_variantes_cot MODIFY COLUMN genero_id JSON NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a BIGINT UNSIGNED
        DB::statement('ALTER TABLE prenda_variantes_cot MODIFY COLUMN genero_id BIGINT UNSIGNED NULL');
        
        // Recrear el índice
        DB::statement('ALTER TABLE prenda_variantes_cot ADD INDEX prenda_variantes_cot_genero_id_foreign (genero_id)');
    }
};
