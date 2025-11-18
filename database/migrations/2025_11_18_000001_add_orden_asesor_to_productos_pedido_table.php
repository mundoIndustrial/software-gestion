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
        Schema::table('productos_pedido', function (Blueprint $table) {
            // Agregar FK para ordenes_asesores (para borradores)
            // Hacer pedido nullable para permitir productos sin pedido
            $table->unsignedBigInteger('orden_asesor_id')->nullable()->after('pedido');
            
            // Hacer pedido nullable si no lo está
            $table->unsignedInteger('pedido')->nullable()->change();
            
            // Agregar FK para orden_asesor_id
            $table->foreign('orden_asesor_id')
                  ->references('id')
                  ->on('ordenes_asesores')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos_pedido', function (Blueprint $table) {
            $table->dropForeign(['orden_asesor_id']);
            $table->dropColumn('orden_asesor_id');
            // No reversamos el cambio de pedido nullable
        });
    }
};
