<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Elimina la columna 'asesora' después de migrar los datos a 'asesor_id'
     */
    public function up(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Eliminar la columna asesora si existe
            if (Schema::hasColumn('pedidos_produccion', 'asesora')) {
                $table->dropColumn('asesora');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Recrear la columna en caso de rollback
            $table->string('asesora', 111)->nullable();
        });
    }
};
