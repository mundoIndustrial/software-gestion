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
        // Agregar columna dia_de_entrega justo ANTES de total_de_dias_
        // Orden: area → dia_de_entrega → total_de_dias_
        DB::statement('ALTER TABLE tabla_original ADD COLUMN dia_de_entrega INT NULL AFTER area');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tabla_original', function (Blueprint $table) {
            $table->dropColumn('dia_de_entrega');
        });
    }
};
