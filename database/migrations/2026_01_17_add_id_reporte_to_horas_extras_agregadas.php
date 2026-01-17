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
        Schema::table('horas_extras_agregadas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_reporte')->nullable()->after('codigo_persona');
            
            $table->foreign('id_reporte')
                ->references('id')
                ->on('reportes_personal')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horas_extras_agregadas', function (Blueprint $table) {
            $table->dropForeign(['id_reporte']);
            $table->dropColumn('id_reporte');
        });
    }
};
