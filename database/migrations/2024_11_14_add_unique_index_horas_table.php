<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('horas', function (Blueprint $table) {
            // Agregar índice ÚNICO para evitar duplicados y acelerar búsquedas
            $table->unique('hora', 'idx_horas_hora_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horas', function (Blueprint $table) {
            $table->dropUnique('idx_horas_hora_unique');
        });
    }
};
