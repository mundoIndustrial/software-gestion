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
            // Cambiar cantidad de varchar(56) a integer
            // Será la suma total de todas las cantidades por talla
            $table->integer('cantidad')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_pedido', function (Blueprint $table) {
            // Revertir a varchar
            $table->string('cantidad', 56)->change();
        });
    }
};
