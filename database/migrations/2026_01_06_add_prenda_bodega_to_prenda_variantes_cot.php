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
        Schema::table('prenda_variantes_cot', function (Blueprint $table) {
            // Agregar campo prenda_bodega - Si está marcado guarda 'si', si no está marcado guarda null
            $table->string('prenda_bodega')->nullable()->default(null)->after('telas_multiples')->comment('Indica si la prenda es de bodega (si/null)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_variantes_cot', function (Blueprint $table) {
            $table->dropColumn('prenda_bodega');
        });
    }
};
