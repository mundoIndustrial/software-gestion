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
        // Cambiar proceso de ENUM a VARCHAR
        Schema::table('procesos_prenda', function (Blueprint $table) {
            $table->string('proceso', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procesos_prenda', function (Blueprint $table) {
            $table->enum('proceso', ['Creación Orden','Inventario','Insumos y Telas','Corte','Bordado','Estampado','Costura','Reflectivo','Lavandería','Arreglos','Control Calidad','Entrega','Despacho'])->change();
        });
    }
};
