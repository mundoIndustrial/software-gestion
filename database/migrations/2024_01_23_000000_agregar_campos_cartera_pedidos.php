<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * IMPORTANTE: Esta migración agrega campos para soportar la funcionalidad
     * de aprobación/rechazo de cartera en la vista cartera_pedidos.blade.php
     */
    public function up(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Campos para aprobación por cartera
            $table->unsignedBigInteger('aprobado_por_usuario_cartera')->nullable()->after('estado');
            $table->timestamp('aprobado_por_cartera_en')->nullable()->after('aprobado_por_usuario_cartera');
            $table->foreign('aprobado_por_usuario_cartera')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Campos para rechazo por cartera
            $table->unsignedBigInteger('rechazado_por_usuario_cartera')->nullable()->after('aprobado_por_cartera_en');
            $table->timestamp('rechazado_por_cartera_en')->nullable()->after('rechazado_por_usuario_cartera');
            $table->text('motivo_rechazo_cartera')->nullable()->after('rechazado_por_cartera_en');
            $table->foreign('rechazado_por_usuario_cartera')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Índices para mejorar performance
            $table->index('estado');
            $table->index('aprobado_por_cartera_en');
            $table->index('rechazado_por_cartera_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex(['estado']);
            $table->dropIndex(['aprobado_por_cartera_en']);
            $table->dropIndex(['rechazado_por_cartera_en']);

            // Eliminar foreign keys
            $table->dropForeign(['aprobado_por_usuario_cartera']);
            $table->dropForeign(['rechazado_por_usuario_cartera']);

            // Eliminar columnas
            $table->dropColumn([
                'aprobado_por_usuario_cartera',
                'aprobado_por_cartera_en',
                'rechazado_por_usuario_cartera',
                'rechazado_por_cartera_en',
                'motivo_rechazo_cartera'
            ]);
        });
    }
};
