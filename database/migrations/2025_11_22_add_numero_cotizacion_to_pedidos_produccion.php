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
            // Agregar número de cotización si no existe
            if (!Schema::hasColumn('pedidos_produccion', 'numero_cotizacion')) {
                $table->string('numero_cotizacion')->nullable()->after('cotizacion_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            $table->dropColumn('numero_cotizacion');
        });
    }
};
