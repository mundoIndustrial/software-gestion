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
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            // Cambiar campos no indexados a TEXT para permitir más caracteres
            // Dejar numero_pedido y talla sin cambios ya que tienen índices
            $table->text('prenda_nombre')->nullable()->change();
            $table->text('asesor')->nullable()->change();
            $table->text('empresa')->nullable()->change();
            $table->text('usuario_bodega_nombre')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            // Revertir a tamaño original
            $table->string('prenda_nombre', 255)->nullable()->change();
            $table->string('asesor', 255)->nullable()->change();
            $table->string('empresa', 255)->nullable()->change();
            $table->string('usuario_bodega_nombre', 255)->nullable()->change();
        });
    }
};
