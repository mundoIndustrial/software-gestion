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
        // Eliminar tabla si existe (de intento anterior)
        Schema::dropIfExists('bodega_notas');
        
        Schema::create('bodega_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_produccion_id')->constrained('pedidos_produccion')->onDelete('cascade');
            $table->string('numero_pedido');
            $table->string('talla');
            $table->text('contenido');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->string('usuario_nombre');
            $table->string('usuario_rol')->nullable(); // Bodeguero, Costura-Bodega, EPP-Bodega
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['numero_pedido', 'talla']);
            $table->index('usuario_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bodega_notas');
    }
};
