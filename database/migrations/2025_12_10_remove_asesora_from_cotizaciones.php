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
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Eliminar el campo asesora ya que tenemos asesor_id
            // El nombre del asesor se obtiene desde la tabla users
            $table->dropColumn('asesora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Restaurar el campo asesora en caso de rollback
            $table->string('asesora')->nullable()->after('cliente');
        });
    }
};
