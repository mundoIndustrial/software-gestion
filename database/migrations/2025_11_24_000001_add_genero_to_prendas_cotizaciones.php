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
        if (!Schema::hasColumn('prendas_cotizaciones', 'genero')) {
            Schema::table('prendas_cotizaciones', function (Blueprint $table) {
                $table->string('genero')->nullable()->after('nombre_producto')->comment('Género seleccionado: Dama, Caballero, Unisex');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_cotizaciones', function (Blueprint $table) {
            if (Schema::hasColumn('prendas_cotizaciones', 'genero')) {
                $table->dropColumn('genero');
            }
        });
    }
};
