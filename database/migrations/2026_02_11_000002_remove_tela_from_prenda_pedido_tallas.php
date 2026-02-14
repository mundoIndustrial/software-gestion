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
            //  Remover el campo tela (ahora en tabla relacional prenda_pedido_talla_colores.tela_nombre)
            if (Schema::hasColumn('prenda_pedido_tallas', 'tela')) {
                $table->dropColumn('tela');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            //  Restaurar el campo tela en caso de rollback
            $table->string('tela', 100)->nullable()->comment('Nombre de la tela (LEGACY - usar prenda_pedido_talla_colores.tela_nombre)');
        });
    }
};
