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
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            // Agregar columna es_sobremedida para marcar si una talla es sobremedida
            // Sobremedida significa: cantidad sin talla específica
            $table->boolean('es_sobremedida')->default(false)->after('talla');

            // Hacer la columna 'talla' nullable cuando es sobremedida
            $table->string('talla', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            // Revertir los cambios
            $table->dropColumn('es_sobremedida');
            // Nota: SQLite y algunos SGBD no permiten simplemente hacer NOT NULL nuevamente
            // Se recomienda hacer rollback completo si es necesario
        });
    }
};
