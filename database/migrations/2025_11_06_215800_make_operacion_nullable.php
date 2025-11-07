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
        // Hacer el campo operacion nullable
        DB::statement("ALTER TABLE operaciones_balanceo MODIFY COLUMN operacion TEXT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver a NOT NULL
        DB::statement("ALTER TABLE operaciones_balanceo MODIFY COLUMN operacion TEXT NOT NULL");
    }
};
