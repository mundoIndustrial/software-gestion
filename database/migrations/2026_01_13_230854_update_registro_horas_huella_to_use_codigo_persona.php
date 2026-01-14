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
        // Primero eliminar el índice que no es una foreign key real
        \DB::statement('ALTER TABLE registro_horas_huella DROP KEY registro_horas_huella_id_persona_foreign');
        
        // Renombrar la columna
        \DB::statement('ALTER TABLE registro_horas_huella CHANGE COLUMN id_persona codigo_persona INT NOT NULL');
        
        // Agregar la foreign key correctamente
        Schema::table('registro_horas_huella', function (Blueprint $table) {
            $table->foreign('codigo_persona')
                ->references('codigo_persona')
                ->on('personal')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_horas_huella', function (Blueprint $table) {
            // Eliminar la nueva foreign key
            $table->dropForeign('registro_horas_huella_codigo_persona_foreign');
        });
        
        // Renombrar la columna de vuelta
        \DB::statement('ALTER TABLE registro_horas_huella CHANGE COLUMN codigo_persona id_persona BIGINT UNSIGNED NOT NULL');
        
        // Cambiar el tipo de dato de vuelta
        \DB::statement('ALTER TABLE registro_horas_huella MODIFY id_persona BIGINT UNSIGNED NOT NULL');
        
        // Recrear la foreign key anterior
        Schema::table('registro_horas_huella', function (Blueprint $table) {
            $table->foreign('id_persona')
                ->references('id')
                ->on('personal')
                ->onDelete('cascade');
        });
    }
};
