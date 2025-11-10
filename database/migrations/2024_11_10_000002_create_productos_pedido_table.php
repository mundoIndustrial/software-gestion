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
        Schema::create('productos_pedido', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pedido');
            $table->string('nombre_producto');
            $table->text('descripcion')->nullable();
            $table->string('talla')->nullable();
            $table->integer('cantidad')->default(1);
            $table->decimal('precio_unitario', 10, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->string('imagen')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            
            // Foreign key
            $table->foreign('pedido')->references('pedido')->on('tabla_original')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos_pedido');
    }
};
