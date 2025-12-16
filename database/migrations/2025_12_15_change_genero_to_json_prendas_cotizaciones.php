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
            // Cambiar genero de varchar(255) a json para permitir múltiples géneros
            $table->json('genero')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_cotizaciones', function (Blueprint $table) {
            // Revertir a varchar(255)
            $table->string('genero', 255)->nullable()->change();
        });
    }
};
