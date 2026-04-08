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
        Schema::table('logo_cotizacion_tecnica_prendas_fotos', function (Blueprint $table) {
            $table->dropColumn('ruta_miniatura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_cotizacion_tecnica_prendas_fotos', function (Blueprint $table) {
            $table->string('ruta_miniatura', 500)->nullable()->after('ruta_webp');
        });
    }
};
