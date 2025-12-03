<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar procesos_prenda: cambiar "Creación Orden" a "Creación de Orden"
        DB::table('procesos_prenda')
            ->where('proceso', 'Creación Orden')
            ->update(['proceso' => 'Creación de Orden']);

        // Actualizar pedidos_produccion: cambiar "Creación Orden" a "Creación de Orden"
        DB::table('pedidos_produccion')
            ->where('area', 'Creación Orden')
            ->update(['area' => 'Creación de Orden']);

        // Actualizar procesos_historial: cambiar "Creación Orden" a "Creación de Orden"
        DB::table('procesos_historial')
            ->where('proceso', 'Creación Orden')
            ->update(['proceso' => 'Creación de Orden']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cambios
        DB::table('procesos_prenda')
            ->where('proceso', 'Creación de Orden')
            ->update(['proceso' => 'Creación Orden']);

        DB::table('pedidos_produccion')
            ->where('area', 'Creación de Orden')
            ->update(['area' => 'Creación Orden']);

        DB::table('procesos_historial')
            ->where('proceso', 'Creación de Orden')
            ->update(['proceso' => 'Creación Orden']);
    }
};
