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
        Schema::table('logo_cotizacion_tecnica_prendas', function (Blueprint $table) {
            $table->dropColumn('nombre_prenda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_cotizacion_tecnica_prendas', function (Blueprint $table) {
            $table->string('nombre_prenda')->after('tipo_logo_id');
        });
    }
};
