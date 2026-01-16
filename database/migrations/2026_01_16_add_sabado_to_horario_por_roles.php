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
        Schema::table('horario_por_roles', function (Blueprint $table) {
            // Agregar horarios para sábado
            $table->time('entrada_sabado')->nullable()->after('salida_tarde');
            $table->time('salida_sabado')->nullable()->after('entrada_sabado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horario_por_roles', function (Blueprint $table) {
            $table->dropColumn(['entrada_sabado', 'salida_sabado']);
        });
    }
};
