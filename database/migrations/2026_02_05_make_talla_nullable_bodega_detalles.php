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
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            // Cambiar talla a nullable para permitir EPPs sin talla
            $table->string('talla')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            $table->string('talla')->nullable(false)->change();
        });
    }
};
