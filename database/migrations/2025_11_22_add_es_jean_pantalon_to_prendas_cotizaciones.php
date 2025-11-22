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
        Schema::table('prendas_cotizaciones', function (Blueprint $table) {
            // Flag para indicar si aplica el selector de Tipo de JEAN/PANTALÓN
            $table->boolean('es_jean_pantalon')->default(false)->after('nombre_producto');
            $table->string('tipo_jean_pantalon')->nullable()->after('es_jean_pantalon'); // 'metalico' o 'plastico'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_cotizaciones', function (Blueprint $table) {
            $table->dropColumn(['es_jean_pantalon', 'tipo_jean_pantalon']);
        });
    }
};
