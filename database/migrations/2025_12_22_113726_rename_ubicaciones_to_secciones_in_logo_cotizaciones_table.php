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
            $table->renameColumn('ubicaciones', 'secciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_cotizaciones', function (Blueprint $table) {
            $table->renameColumn('secciones', 'ubicaciones');
        });
    }
};
