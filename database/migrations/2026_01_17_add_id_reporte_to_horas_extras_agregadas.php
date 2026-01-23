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
        // Migración vacía: la tabla horas_extras_agregadas ya existe con la columna id_reporte
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horas_extras_agregadas', function (Blueprint $table) {
            $table->dropForeign(['id_reporte']);
            $table->dropColumn('id_reporte');
        });
    }
};
