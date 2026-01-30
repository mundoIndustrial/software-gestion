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
            $table->string('tipo', 50)->primary(); // 'pedido_produccion'
            $table->unsignedBigInteger('siguiente')->default(1);
            $table->unsignedBigInteger('ultimo_usado')->default(0);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
            
            $table->index(['tipo']);
        });
        
        // Insertar secuencia inicial para pedidos de producción
        DB::table('numero_secuencias')->insert([
            'tipo' => 'pedido_produccion',
            'siguiente' => 1,
            'ultimo_usado' => 0,
            'descripcion' => 'Secuencia para números de pedido de producción',
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
