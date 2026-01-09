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
            // Agregar columna para indicar si la prenda se saca de bodega
            $table->boolean('de_bodega')->default(false)->comment('Indica si la prenda se saca de bodega existente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_pedido', function (Blueprint $table) {
            $table->dropColumn('de_bodega');
        });
    }
};
