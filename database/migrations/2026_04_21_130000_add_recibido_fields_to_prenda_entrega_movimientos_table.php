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
        Schema::table('prenda_entrega_movimientos', function (Blueprint $table) {
            $table->timestamp('fecha_recibido')->nullable()->comment('Fecha y hora cuando se confirma la recepción en despacho');
            $table->unsignedBigInteger('usuario_recibido_id')->nullable()->comment('Usuario que confirma la recepción');
            $table->enum('estado', ['pendiente', 'recibido'])->default('pendiente')->comment('Estado de recepción');

            $table->foreign('usuario_recibido_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_entrega_movimientos', function (Blueprint $table) {
            $table->dropForeign(['usuario_recibido_id']);
            $table->dropColumn(['fecha_recibido', 'usuario_recibido_id', 'estado']);
        });
    }
};
