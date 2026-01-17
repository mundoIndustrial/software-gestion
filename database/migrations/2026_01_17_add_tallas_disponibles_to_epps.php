<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agregar columna tallas_disponibles a tabla epps
     */
    public function up(): void
    {
        Schema::table('epps', function (Blueprint $table) {
            $table->json('tallas_disponibles')->nullable()->after('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('epps', function (Blueprint $table) {
            $table->dropColumn('tallas_disponibles');
        });
    }
};
