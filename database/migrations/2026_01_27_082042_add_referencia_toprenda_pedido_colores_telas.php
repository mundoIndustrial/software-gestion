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
        Schema::table('prenda_pedido_colores_telas', function (Blueprint $table) {
            $table->string('referencia')->nullable()->after('tela_id')->comment('Referencia de la tela para este pedido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_pedido_colores_telas', function (Blueprint $table) {
            $table->dropColumn('referencia');
        });
    }
};
