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
        // Agregar columna fecha_estimada_de_entrega después de fecha_de_creacion_de_orden
        DB::statement('ALTER TABLE tabla_original ADD COLUMN fecha_estimada_de_entrega DATE NULL AFTER fecha_de_creacion_de_orden');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tabla_original', function (Blueprint $table) {
            $table->dropColumn('fecha_estimada_de_entrega');
        });
    }
};
