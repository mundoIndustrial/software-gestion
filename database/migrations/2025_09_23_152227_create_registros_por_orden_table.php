<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_por_orden', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pedido');
            $table->string('cliente', 96);
            $table->string('prenda', 168);
            $table->text('descripcion');
            $table->string('talla', 50);
            $table->string('cantidad', 60);
            $table->string('costurero', 61)->nullable();
            $table->integer('total_producido_por_talla')->nullable();
            $table->integer('total_pendiente_por_talla')->nullable();
            $table->date('fecha_completado')->nullable();

            // Agregar clave foránea para asegurar integridad referencial
            $table->foreign('pedido')
                  ->references('pedido')
                  ->on('tabla_original')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_por_orden');
    }
};
