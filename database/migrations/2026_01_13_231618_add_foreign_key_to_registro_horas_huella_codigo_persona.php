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
            $table->dropForeign('registro_horas_huella_codigo_persona_foreign');
        });
    }
};
