<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar índice UNIQUE a la tabla pedidos_procesos_prenda_tallas
     */
    public function up(): void
    {
        Schema::table('pedidos_procesos_prenda_tallas', function (Blueprint $table) {
            // Verificar si el índice ya existe
            $table->unique(['proceso_prenda_detalle_id', 'genero', 'talla'], 'uq_proc_prenda_genero_talla');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_procesos_prenda_tallas', function (Blueprint $table) {
            $table->dropUnique('uq_proc_prenda_genero_talla');
        });
    }
};
