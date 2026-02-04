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
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Agregar columnas para el motivo de anulación/revisión
            $table->text('motivo_anulacion')->nullable()->after('estado');
            $table->timestamp('fecha_anulacion')->nullable()->after('motivo_anulacion');
            $table->string('usuario_anulacion')->nullable()->after('fecha_anulacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            $table->dropColumn(['motivo_anulacion', 'fecha_anulacion', 'usuario_anulacion']);
        });
    }
};
