<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agregar campo novedades a procesos_prenda para guardar reportes de novedad
     */
    public function up(): void
    {
        Schema::table('procesos_prenda', function (Blueprint $table) {
            $table->longText('novedades')->nullable()->after('observaciones')->comment('Novedades reportadas por el operario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procesos_prenda', function (Blueprint $table) {
            $table->dropColumn('novedades');
        });
    }
};
