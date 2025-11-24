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
            // Agregar foreign key a tipos_cotizacion si no existe
            if (!Schema::hasColumn('cotizaciones', 'tipo_cotizacion_id')) {
                $table->foreignId('tipo_cotizacion_id')
                    ->nullable()
                    ->after('numero_cotizacion')
                    ->constrained('tipos_cotizacion')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropForeignKey(['tipo_cotizacion_id']);
            $table->dropColumn('tipo_cotizacion_id');
        });
    }
};
