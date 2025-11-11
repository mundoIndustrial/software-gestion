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
        Schema::table('ordenes_asesores', function (Blueprint $table) {
            // Agregar número de pedido consecutivo (solo para confirmados)
            $table->integer('pedido')->nullable()->after('numero_orden');
            
            // Agregar campo de estado del pedido
            $table->enum('estado_pedido', ['borrador', 'confirmado', 'en_proceso', 'completado', 'cancelado'])
                  ->default('borrador')
                  ->after('estado');
            
            // Agregar campo para identificar borradores
            $table->boolean('es_borrador')->default(true)->after('estado_pedido');
            
            // Agregar fecha de confirmación (cuando deja de ser borrador)
            $table->timestamp('fecha_confirmacion')->nullable()->after('es_borrador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_asesores', function (Blueprint $table) {
            $table->dropColumn(['pedido', 'estado_pedido', 'es_borrador', 'fecha_confirmacion']);
        });
    }
};
