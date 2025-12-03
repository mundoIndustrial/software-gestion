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
        // Solo agregar el campo productos si no existe
        if (Schema::hasTable('prendas_cotizaciones')) {
            Schema::table('prendas_cotizaciones', function (Blueprint $table) {
                if (!Schema::hasColumn('prendas_cotizaciones', 'productos')) {
                    $table->json('productos')->nullable()->after('telas');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('prendas_cotizaciones')) {
            Schema::table('prendas_cotizaciones', function (Blueprint $table) {
                $table->dropColumn(['productos']);
            });
        }
    }
};
