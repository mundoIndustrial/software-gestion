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
            // Renombrar columnas de anulacion a revision
            $table->renameColumn('motivo_anulacion', 'motivo_revision');
            $table->renameColumn('fecha_anulacion', 'fecha_revision');
            $table->renameColumn('usuario_anulacion', 'usuario_revision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Revertir nombres
            $table->renameColumn('motivo_revision', 'motivo_anulacion');
            $table->renameColumn('fecha_revision', 'fecha_anulacion');
            $table->renameColumn('usuario_revision', 'usuario_anulacion');
        });
    }
};
