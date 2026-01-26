<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar índices para optimizar búsquedas de EPP
     * Mejora significativa en performance de queries
     */
    public function up(): void
    {
        Schema::table('epps', function (Blueprint $table) {
            // Índice compuesto para búsquedas rápidas
            $table->index(['activo', 'nombre_completo']);
            $table->index(['activo', 'codigo']);
            $table->index(['activo', 'marca']);
            $table->index('categoria_id');
        });

        // Log para confirmar
        \Illuminate\Support\Facades\Log::info('✅ Índices de EPP creados para optimización');
    }

    /**
     * Revert the migrations.
     */
    public function down(): void
    {
        Schema::table('epps', function (Blueprint $table) {
            $table->dropIndex(['activo', 'nombre_completo']);
            $table->dropIndex(['activo', 'codigo']);
            $table->dropIndex(['activo', 'marca']);
            $table->dropIndex(['categoria_id']);
        });
    }
};
