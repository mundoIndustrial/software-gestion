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
        Schema::table('seguimiento_pedidos_por_prenda', function (Blueprint $table) {
            // Añadir relación con procesos_prenda
            $table->unsignedBigInteger('proceso_prenda_id')->nullable()->after('prenda_id');
            $table->foreign('proceso_prenda_id')->references('id')->on('procesos_prenda')->onDelete('cascade');
            
            // Añadir campos de estado y área
            $table->string('area')->nullable()->after('tipo_recibo');
            $table->enum('estado', ['Pendiente', 'En Progreso', 'Completado', 'Pausado'])->default('Pendiente')->after('area');
            $table->dateTime('fecha_inicio')->nullable()->after('estado');
            $table->dateTime('fecha_fin')->nullable()->after('fecha_inicio');
            $table->string('encargado')->nullable()->after('fecha_fin');
            $table->text('observaciones')->nullable()->after('encargado');
            
            // Índices adicionales
            $table->index(['proceso_prenda_id', 'estado'], 'seguimiento_proceso_estado_idx');
            $table->index(['pedido_produccion_id', 'prenda_id', 'area'], 'seguimiento_prenda_area_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seguimiento_pedidos_por_prenda', function (Blueprint $table) {
            $table->dropForeign(['proceso_prenda_id']);
            $table->dropIndex('seguimiento_proceso_estado_idx');
            $table->dropIndex('seguimiento_prenda_area_idx');
            $table->dropColumn([
                'proceso_prenda_id',
                'area',
                'estado',
                'fecha_inicio',
                'fecha_fin',
                'encargado',
                'observaciones'
            ]);
        });
    }
};
