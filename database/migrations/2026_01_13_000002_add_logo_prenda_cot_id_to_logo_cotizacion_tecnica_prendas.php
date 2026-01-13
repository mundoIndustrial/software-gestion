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
            // Agregar la foreign key a logo_prenda_cot
            $table->unsignedBigInteger('logo_prenda_cot_id')->nullable()->after('tipo_logo_id');
            
            // Crear el índice
            $table->index('logo_prenda_cot_id');
            
            // Foreign key
            $table->foreign('logo_prenda_cot_id')
                ->references('id')
                ->on('logo_prenda_cot')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_cotizacion_tecnica_prendas', function (Blueprint $table) {
            $table->dropForeign(['logo_prenda_cot_id']);
            $table->dropIndex(['logo_prenda_cot_id']);
            $table->dropColumn('logo_prenda_cot_id');
        });
    }
};
