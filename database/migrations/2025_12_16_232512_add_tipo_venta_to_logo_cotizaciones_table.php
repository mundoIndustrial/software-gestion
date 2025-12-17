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
        Schema::table('logo_cotizaciones', function (Blueprint $table) {
            $table->string('tipo_venta')->nullable()->after('cotizacion_id')->comment('Tipo de venta: M, D, X');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_cotizaciones', function (Blueprint $table) {
            $table->dropColumn('tipo_venta');
        });
    }
};
