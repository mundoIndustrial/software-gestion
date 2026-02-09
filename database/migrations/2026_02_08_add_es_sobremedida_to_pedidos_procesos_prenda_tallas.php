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
        Schema::table('pedidos_procesos_prenda_tallas', function (Blueprint $table) {
            // Hacer talla nullable para soportar sobremedida (que no tiene talla específica)
            $table->string('talla', 50)->nullable()->change();
            
            // Agregar flag para identificar si es sobremedida
            $table->boolean('es_sobremedida')->default(false)->after('cantidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_procesos_prenda_tallas', function (Blueprint $table) {
            // Revertir cambios
            $table->string('talla', 50)->nullable(false)->change();
            $table->dropColumn('es_sobremedida');
        });
    }
};
