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
        Schema::table('pedido_ancho_metraje', function (Blueprint $table) {
            // Drop the old unique constraint on pedido_produccion_id alone
            $table->dropUnique('pedido_ancho_metraje_pedido_produccion_id_unique');
            // Add composite unique on (pedido_produccion_id, prenda_pedido_id)
            $table->unique(['pedido_produccion_id', 'prenda_pedido_id'], 'pedido_ancho_metraje_pedido_prenda_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_ancho_metraje', function (Blueprint $table) {
            $table->dropUnique('pedido_ancho_metraje_pedido_prenda_unique');
            $table->unique('pedido_produccion_id', 'pedido_ancho_metraje_pedido_produccion_id_unique');
        });
    }
};
