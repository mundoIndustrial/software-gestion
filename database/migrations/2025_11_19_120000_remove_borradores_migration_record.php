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
        // Eliminar el registro de la migración de borradores de la tabla migrations
        DB::table('migrations')->where('migration', '2025_11_19_105201_create_borradores_table')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada en el rollback
    }
};
