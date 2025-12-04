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
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Cambiar fecha_de_creacion_de_orden de DATE a DATETIME para incluir hora
            $table->dateTime('fecha_de_creacion_de_orden')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Revertir a DATE
            $table->date('fecha_de_creacion_de_orden')->nullable()->change();
        });
    }
};
