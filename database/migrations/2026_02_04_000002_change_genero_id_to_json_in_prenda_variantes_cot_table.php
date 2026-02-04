<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
{
    // Eliminar únicamente el índice (la FK ya no existe)
    DB::statement("
        ALTER TABLE prenda_variantes_cot
        DROP INDEX prenda_variantes_cot_genero_id_foreign
    ");

    // Convertir la columna a JSON
    DB::statement("
        ALTER TABLE prenda_variantes_cot
        MODIFY genero_id JSON NULL
    ");
}

    public function down(): void
    {
        // 1️⃣ Volver a BIGINT
        DB::statement("
            ALTER TABLE prenda_variantes_cot
            MODIFY genero_id BIGINT UNSIGNED NULL
        ");

        // 2️⃣ Recrear índice
        DB::statement("
            ALTER TABLE prenda_variantes_cot
            ADD INDEX prenda_variantes_cot_genero_id_foreign (genero_id)
        ");

        // 3️⃣ Recrear foreign key
        DB::statement("
            ALTER TABLE prenda_variantes_cot
            ADD CONSTRAINT prenda_variantes_cot_genero_id_foreign
            FOREIGN KEY (genero_id)
            REFERENCES generos_prenda(id)
            ON DELETE SET NULL
        ");
    }
};