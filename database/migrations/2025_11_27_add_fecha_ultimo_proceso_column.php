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
            // Agregar columna fecha_ultimo_proceso si no existe
            if (!Schema::hasColumn('pedidos_produccion', 'fecha_ultimo_proceso')) {
                $table->timestamp('fecha_ultimo_proceso')->nullable()->after('fecha_estimada_de_entrega');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            $table->dropColumn('fecha_ultimo_proceso');
        });
    }
};
