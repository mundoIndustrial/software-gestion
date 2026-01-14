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
        // Renombrar procesos_prenda_detalles a pedidos_procesos_prenda_detalles
        Schema::rename('procesos_prenda_detalles', 'pedidos_procesos_prenda_detalles');

        // Renombrar procesos_imagenes a pedidos_procesos_imagenes
        Schema::rename('procesos_imagenes', 'pedidos_procesos_imagenes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir: renombrar de vuelta a los nombres originales
        Schema::rename('pedidos_procesos_prenda_detalles', 'procesos_prenda_detalles');
        Schema::rename('pedidos_procesos_imagenes', 'procesos_imagenes');
    }
};
