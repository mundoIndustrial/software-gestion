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
        Schema::table('maquinas', function (Blueprint $table) {
            // Agregar índice en 'nombre_maquina' para búsquedas rápidas
            $table->index('nombre_maquina', 'idx_maquinas_nombre');
        });
        
        Schema::table('telas', function (Blueprint $table) {
            // Agregar índice en 'nombre_tela' para búsquedas rápidas
            $table->index('nombre_tela', 'idx_telas_nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maquinas', function (Blueprint $table) {
            $table->dropIndex('idx_maquinas_nombre');
        });
        
        Schema::table('telas', function (Blueprint $table) {
            $table->dropIndex('idx_telas_nombre');
        });
    }
};
