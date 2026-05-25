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
        Schema::table('despacho_ajustes_detalles', function (Blueprint $table) {
            $table->unsignedBigInteger('talla_color_id')->nullable()->after('talla_id');
            $table->string('genero', 50)->nullable()->after('talla_color_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('despacho_ajustes_detalles', function (Blueprint $table) {
            $table->dropColumn(['talla_color_id', 'genero']);
        });
    }
};
