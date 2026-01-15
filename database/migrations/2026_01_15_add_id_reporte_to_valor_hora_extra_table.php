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
        Schema::table('valor_hora_extra', function (Blueprint $table) {
            // Agregar columna id_reporte como foreign key
            $table->unsignedBigInteger('id_reporte')->nullable()->after('codigo_persona');
            
            // Crear relación con la tabla reportes_personal
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
        Schema::table('valor_hora_extra', function (Blueprint $table) {
            // Eliminar la clave foránea
            $table->dropForeign(['id_reporte']);
            // Eliminar la columna
            $table->dropColumn('id_reporte');
        });
    }
};
