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
        Schema::table('reflectivo_cotizacion', function (Blueprint $table) {
            $table->string('tipo_prenda')->nullable()->after('cotizacion_id')->comment('Tipo de prenda (Camiseta, Pantalón, etc)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reflectivo_cotizacion', function (Blueprint $table) {
            $table->dropColumn('tipo_prenda');
        });
    }
};
