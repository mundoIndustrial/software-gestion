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
            $table->dropColumn(['ancho', 'alto', 'tamaño']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_cotizacion_tecnica_prendas_fotos', function (Blueprint $table) {
            $table->integer('ancho')->nullable()->after('orden');
            $table->integer('alto')->nullable()->after('ancho');
            $table->integer('tamaño')->nullable()->after('alto');
        });
    }
};
