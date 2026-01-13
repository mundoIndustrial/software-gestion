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
        Schema::table('registro_de_huella', function (Blueprint $table) {
            // Eliminar la columna hora
            $table->dropColumn('hora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_de_huella', function (Blueprint $table) {
            // Restaurar la columna hora
            $table->time('hora')->nullable();
        });
    }
};
