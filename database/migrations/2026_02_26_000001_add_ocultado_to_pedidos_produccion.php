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
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Agregar campo para ocultar pedidos en supervisor-pedidos
            $table->timestamp('ocultado_en')->nullable()->after('deleted_at');
            $table->unsignedBigInteger('usuario_ocultado_por')->nullable()->after('ocultado_en');
            
            // Index para búsquedas rápidas
            $table->index(['ocultado_en'], 'pedidos_ocultado_idx');
            
            // Foreign key para el usuario que ocultó
            $table->foreign('usuario_ocultado_por')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            $table->dropForeign(['usuario_ocultado_por']);
            $table->dropIndex('pedidos_ocultado_idx');
            $table->dropColumn(['ocultado_en', 'usuario_ocultado_por']);
        });
    }
};
