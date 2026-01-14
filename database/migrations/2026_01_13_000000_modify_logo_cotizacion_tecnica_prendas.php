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
        Schema::table('logo_cotizacion_tecnica_prendas', function (Blueprint $table) {
            // Agregar prenda_cot_id con relación a prendas_cot
            $table->unsignedBigInteger('prenda_cot_id')->nullable()->after('tipo_logo_id');
            
            // Crear la relación con prendas_cot
            $table->foreign('prenda_cot_id')
                ->references('id')
                ->on('prendas_cot')
                ->onDelete('cascade');
            
            // Eliminar el campo nombre_prenda
            $table->dropColumn('nombre_prenda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_cotizacion_tecnica_prendas', function (Blueprint $table) {
            // Revertir la relación
            $table->dropForeign(['prenda_cot_id']);
            $table->dropColumn('prenda_cot_id');
            
            // Restaurar el campo nombre_prenda
            $table->string('nombre_prenda')->after('tipo_logo_id');
        });
    }
};
