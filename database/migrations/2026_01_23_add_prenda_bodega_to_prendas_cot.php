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
        Schema::table('prendas_cot', function (Blueprint $table) {
            // Agregar campo prenda_bodega si no existe
            if (!Schema::hasColumn('prendas_cot', 'prenda_bodega')) {
                $table->boolean('prenda_bodega')->default(false)->after('cantidad')->comment('Indica si la prenda viene de bodega');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_cot', function (Blueprint $table) {
            if (Schema::hasColumn('prendas_cot', 'prenda_bodega')) {
                $table->dropColumn('prenda_bodega');
            }
        });
    }
};
