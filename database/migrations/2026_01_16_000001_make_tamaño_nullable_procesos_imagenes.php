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
        Schema::table('pedidos_procesos_imagenes', function (Blueprint $table) {
            // Cambiar tamaño a nullable
            $table->bigInteger('tamaño')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_procesos_imagenes', function (Blueprint $table) {
            // Revertir a NOT NULL
            $table->bigInteger('tamaño')->nullable(false)->change();
        });
    }
};
