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
            // Cambiar imagen_tela a telas (JSON array)
            if (Schema::hasColumn('prendas_cotizaciones', 'imagen_tela')) {
                $table->dropColumn('imagen_tela');
            }
            
            if (!Schema::hasColumn('prendas_cotizaciones', 'telas')) {
                $table->json('telas')->nullable()->after('fotos');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_cotizaciones', function (Blueprint $table) {
            // Revertir cambios
            if (Schema::hasColumn('prendas_cotizaciones', 'telas')) {
                $table->dropColumn('telas');
            }
            
            if (!Schema::hasColumn('prendas_cotizaciones', 'imagen_tela')) {
                $table->string('imagen_tela')->nullable()->after('fotos');
            }
        });
    }
};
