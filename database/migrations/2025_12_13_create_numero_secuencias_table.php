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
        Schema::create('numero_secuencias', function (Blueprint $table) {
            $table->id();
            $table->string('tipo')->unique(); // 'pedido_produccion', etc
            $table->bigInteger('siguiente')->default(1);
            $table->timestamps();
        });

        // Insertar secuencia inicial para pedidos de producción
        DB::table('numero_secuencias')->insert([
            'tipo' => 'pedido_produccion',
            'siguiente' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('numero_secuencias');
    }
};
