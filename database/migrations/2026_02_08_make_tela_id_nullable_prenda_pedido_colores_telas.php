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
            // Permitir NULL en tela_id para poder guardar solo colores sin tela
            $table->unsignedBigInteger('tela_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_pedido_colores_telas', function (Blueprint $table) {
            $table->unsignedBigInteger('tela_id')->nullable(false)->change();
        });
    }
};
