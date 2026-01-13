<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_de_huella', function (Blueprint $table) {
            $table->unsignedBigInteger('id_reporte')->nullable()->after('id_persona');
            
            $table->foreign('id_reporte')
                ->references('id')
                ->on('reportes_personal')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('registro_de_huella', function (Blueprint $table) {
            $table->dropForeign(['id_reporte']);
            $table->dropColumn('id_reporte');
        });
    }
};
