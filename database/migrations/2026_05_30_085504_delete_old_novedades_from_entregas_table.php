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
        // Eliminar todas las novedades antiguas de la tabla entrega_recibo_costura
        // (registros donde talla = 'NOVEDAD' y cantidad_entregada = 0)
        DB::table('entrega_recibo_costura')
            ->where('talla', 'NOVEDAD')
            ->where('cantidad_entregada', 0)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se puede revertir esta migración ya que los datos fueron eliminados
        // Esta es una migración de limpieza de datos
    }
};
