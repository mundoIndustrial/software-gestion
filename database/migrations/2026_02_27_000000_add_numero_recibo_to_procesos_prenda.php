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
        Schema::table('procesos_prenda', function (Blueprint $table) {
            // Agregar el campo numero_recibo después de prenda_pedido_id
            $table->integer('numero_recibo')->nullable()->after('prenda_pedido_id');
            
            // Crear índice para búsquedas rápidas
            $table->index(['numero_pedido', 'numero_recibo', 'proceso']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procesos_prenda', function (Blueprint $table) {
            $table->dropIndex(['numero_pedido', 'numero_recibo', 'proceso']);
            $table->dropColumn('numero_recibo');
        });
    }
};
