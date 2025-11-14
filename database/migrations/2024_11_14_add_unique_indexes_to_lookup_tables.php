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
        // Agregar índice único a la tabla horas (si no existe)
        Schema::table('horas', function (Blueprint $table) {
            // Verificar si el índice ya existe antes de añadirlo
            if (!Schema::hasColumn('horas', 'hora')) {
                return; // Tabla no tiene la columna, saltar
            }
            
            try {
                $table->unique('hora')->change();
            } catch (\Exception $e) {
                // Si falla, probablemente el índice ya existe
                \Log::info('Index already exists or table structure issue on horas: ' . $e->getMessage());
            }
        });

        // Agregar índice único a la tabla maquinas
        Schema::table('maquinas', function (Blueprint $table) {
            if (!Schema::hasColumn('maquinas', 'nombre_maquina')) {
                return;
            }
            
            try {
                $table->unique('nombre_maquina')->change();
            } catch (\Exception $e) {
                \Log::info('Index already exists or table structure issue on maquinas: ' . $e->getMessage());
            }
        });

        // Agregar índice único a la tabla telas
        Schema::table('telas', function (Blueprint $table) {
            if (!Schema::hasColumn('telas', 'nombre_tela')) {
                return;
            }
            
            try {
                $table->unique('nombre_tela')->change();
            } catch (\Exception $e) {
                \Log::info('Index already exists or table structure issue on telas: ' . $e->getMessage());
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover índices únicos
        Schema::table('horas', function (Blueprint $table) {
            try {
                $table->dropUnique(['hora']);
            } catch (\Exception $e) {
                // Index might not exist
            }
        });

        Schema::table('maquinas', function (Blueprint $table) {
            try {
                $table->dropUnique(['nombre_maquina']);
            } catch (\Exception $e) {
                // Index might not exist
            }
        });

        Schema::table('telas', function (Blueprint $table) {
            try {
                $table->dropUnique(['nombre_tela']);
            } catch (\Exception $e) {
                // Index might not exist
            }
        });
    }
};
