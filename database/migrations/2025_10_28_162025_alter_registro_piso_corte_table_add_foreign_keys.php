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
        Schema::table('registro_piso_corte', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['hora', 'cortador', 'maquina', 'tela']);

            // Add new foreign key columns
            $table->foreignId('hora_id')->constrained('horas')->onDelete('cascade');
            $table->foreignId('operario_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('maquina_id')->constrained('maquinas')->onDelete('cascade');
            $table->foreignId('tela_id')->constrained('telas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_piso_corte', function (Blueprint $table) {
            // Drop new columns
            $table->dropForeign(['hora_id']);
            $table->dropForeign(['operario_id']);
            $table->dropForeign(['maquina_id']);
            $table->dropForeign(['tela_id']);
            $table->dropColumn(['hora_id', 'operario_id', 'maquina_id', 'tela_id']);

            // Add back old columns
            $table->string('hora');
            $table->string('cortador');
            $table->string('maquina');
            $table->string('tela');
        });
    }
};
