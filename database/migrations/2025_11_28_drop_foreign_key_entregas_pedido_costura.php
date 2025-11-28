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
        Schema::table('entregas_pedido_costura', function (Blueprint $table) {
            // Eliminar la foreign key que referencia a tabla_original
            $table->dropForeign(['pedido']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entregas_pedido_costura', function (Blueprint $table) {
            // Restaurar la foreign key (opcional)
            $table->foreign('pedido')
                  ->references('pedido')
                  ->on('tabla_original')
                  ->onDelete('cascade');
        });
    }
};
