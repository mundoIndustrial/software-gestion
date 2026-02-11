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
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            // ✅ Remover el campo colores (JSON legacy)
            // Los colores se guardan ahora en la tabla relacional prenda_pedido_talla_colores
            $table->dropColumn('colores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            // ✅ Restaurar el campo colores en caso de rollback
            $table->json('colores')->nullable()->comment('JSON de colores asignados (LEGACY - usar prenda_pedido_talla_colores)');
        });
    }
};
