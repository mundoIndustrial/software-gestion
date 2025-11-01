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
        // Eliminar columnas antiguas solo si existen
        foreach (['hora', 'cortador', 'maquina', 'tela'] as $oldColumn) {
            if (Schema::hasColumn('registro_piso_corte', $oldColumn)) {
                Schema::table('registro_piso_corte', function (Blueprint $table) use ($oldColumn) {
                    $table->dropColumn($oldColumn);
                });
            }
        }

        // Agregar nuevas columnas FK si aún no existen
        if (!Schema::hasColumn('registro_piso_corte', 'hora_id')) {
            Schema::table('registro_piso_corte', function (Blueprint $table) {
                $table->foreignId('hora_id')->constrained('horas')->onDelete('cascade');
            });
        }
        if (!Schema::hasColumn('registro_piso_corte', 'operario_id')) {
            Schema::table('registro_piso_corte', function (Blueprint $table) {
                $table->foreignId('operario_id')->constrained('users')->onDelete('cascade');
            });
        }
        if (!Schema::hasColumn('registro_piso_corte', 'maquina_id')) {
            Schema::table('registro_piso_corte', function (Blueprint $table) {
                $table->foreignId('maquina_id')->constrained('maquinas')->onDelete('cascade');
            });
        }
        if (!Schema::hasColumn('registro_piso_corte', 'tela_id')) {
            Schema::table('registro_piso_corte', function (Blueprint $table) {
                $table->foreignId('tela_id')->constrained('telas')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar nuevas FKs/columnas solo si existen
        foreach (['hora_id', 'operario_id', 'maquina_id', 'tela_id'] as $fkColumn) {
            if (Schema::hasColumn('registro_piso_corte', $fkColumn)) {
                Schema::table('registro_piso_corte', function (Blueprint $table) use ($fkColumn) {
                    $table->dropForeign([$fkColumn]);
                    $table->dropColumn($fkColumn);
                });
            }
        }

        // Restaurar columnas antiguas si no existen
        $columnsToRestore = [
            'hora' => fn (Blueprint $table) => $table->string('hora'),
            'cortador' => fn (Blueprint $table) => $table->string('cortador'),
            'maquina' => fn (Blueprint $table) => $table->string('maquina'),
            'tela' => fn (Blueprint $table) => $table->string('tela'),
        ];
        foreach ($columnsToRestore as $column => $adder) {
            if (!Schema::hasColumn('registro_piso_corte', $column)) {
                Schema::table('registro_piso_corte', function (Blueprint $table) use ($adder) {
                    $adder($table);
                });
            }
        }
    }
};
