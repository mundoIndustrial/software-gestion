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
        Schema::table('prenda_tela_fotos_cot', function (Blueprint $table) {
            $table->dropColumn(['ancho', 'alto', 'tamaño']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_tela_fotos_cot', function (Blueprint $table) {
            $table->integer('ancho')->nullable();
            $table->integer('alto')->nullable();
            $table->integer('tamaño')->nullable();
        });
    }
};
