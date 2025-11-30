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
        Schema::table('prendas_pedido', function (Blueprint $table) {
            // Agregar columna numero_pedido basado en pedido_produccion_id
            $table->string('numero_pedido')->nullable()->after('pedido_produccion_id');
        });

        // Llenar la columna numero_pedido con los datos de pedidos_produccion
        \DB::statement('
            UPDATE prendas_pedido pp
            JOIN pedidos_produccion pp2 ON pp.pedido_produccion_id = pp2.id
            SET pp.numero_pedido = pp2.numero_pedido
        ');

        // Hacer la columna NOT NULL después de llenarla
        Schema::table('prendas_pedido', function (Blueprint $table) {
            $table->string('numero_pedido')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_pedido', function (Blueprint $table) {
            $table->dropColumn('numero_pedido');
        });
    }
};
